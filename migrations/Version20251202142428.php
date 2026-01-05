<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202142428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6389B6B5FBA FOREIGN KEY (account_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_4C62E6389B6B5FBA ON contact (account_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6389B6B5FBA');
        $this->addSql('DROP INDEX IDX_4C62E6389B6B5FBA ON contact');
        $this->addSql('ALTER TABLE contact DROP account_id');
    }
}
