<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create funding_request_deletion_request table for admin-approved dossier deletion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE funding_request_deletion_request (
    id INT AUTO_INCREMENT NOT NULL,
    funding_request_id INT DEFAULT NULL,
    requested_by_id INT DEFAULT NULL,
    decided_by_id INT DEFAULT NULL,
    status VARCHAR(20) NOT NULL,
    reason LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    decided_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_DA590A183407A3A7 (funding_request_id),
    INDEX IDX_DA590A187EE7A6AF (requested_by_id),
    INDEX IDX_DA590A184A46A31D (decided_by_id),
    INDEX idx_fr_delete_request_status (status),
    INDEX idx_fr_delete_request_created_at (created_at),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql('ALTER TABLE funding_request_deletion_request ADD CONSTRAINT FK_DA590A183407A3A7 FOREIGN KEY (funding_request_id) REFERENCES funding_request (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE funding_request_deletion_request ADD CONSTRAINT FK_DA590A187EE7A6AF FOREIGN KEY (requested_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE funding_request_deletion_request ADD CONSTRAINT FK_DA590A184A46A31D FOREIGN KEY (decided_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE funding_request_deletion_request');
    }
}
