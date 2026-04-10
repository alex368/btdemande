<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Contact archive + stage history + opportunity commercial referent and lead source detail';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact ADD is_archived TINYINT(1) NOT NULL DEFAULT 0, ADD archived_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE opportunity ADD commercial_referent_id INT DEFAULT NULL, ADD lead_source_detail VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE opportunity CHANGE stage stage VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE opportunity ADD CONSTRAINT FK_FC8A36EA4CD8A4A4 FOREIGN KEY (commercial_referent_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_FC8A36EA4CD8A4A4 ON opportunity (commercial_referent_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE contact_stage_history (
    id INT AUTO_INCREMENT NOT NULL,
    contact_id INT NOT NULL,
    updated_by_id INT DEFAULT NULL,
    stage VARCHAR(32) NOT NULL,
    occurred_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_26B9E2F5E7A1254A (contact_id),
    INDEX IDX_26B9E2F516FE72E1 (updated_by_id),
    UNIQUE INDEX uniq_contact_stage (contact_id, stage),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql('ALTER TABLE contact_stage_history ADD CONSTRAINT FK_26B9E2F5E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_stage_history ADD CONSTRAINT FK_26B9E2F516FE72E1 FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql(<<<'SQL'
INSERT INTO contact_stage_history (contact_id, stage, occurred_at, updated_by_id)
SELECT
    o.contact_id,
    mapped_stage.stage,
    COALESCE(MAX(o.created_at), NOW()),
    NULL
FROM opportunity o
JOIN (
    SELECT 'prospect' AS source_stage, 'prospect' AS stage
    UNION ALL SELECT 'qualification', 'qualification'
    UNION ALL SELECT 'proposal', 'proposition'
    UNION ALL SELECT 'negotiation', 'negociation'
    UNION ALL SELECT 'won', 'gagne'
    UNION ALL SELECT 'lost', 'perdu'
) AS mapped_stage ON mapped_stage.source_stage = o.stage
WHERE o.contact_id IS NOT NULL
GROUP BY o.contact_id, mapped_stage.stage
ON DUPLICATE KEY UPDATE occurred_at = VALUES(occurred_at)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact_stage_history DROP FOREIGN KEY FK_26B9E2F5E7A1254A');
        $this->addSql('ALTER TABLE contact_stage_history DROP FOREIGN KEY FK_26B9E2F516FE72E1');
        $this->addSql('DROP TABLE contact_stage_history');

        $this->addSql('ALTER TABLE opportunity DROP FOREIGN KEY FK_FC8A36EA4CD8A4A4');
        $this->addSql('DROP INDEX IDX_FC8A36EA4CD8A4A4 ON opportunity');
        $this->addSql('ALTER TABLE opportunity DROP commercial_referent_id, DROP lead_source_detail');
        $this->addSql('ALTER TABLE opportunity CHANGE stage stage VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE contact DROP is_archived, DROP archived_at');
    }
}
