<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125132948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campany DROP FOREIGN KEY FK_75B5683FE7A1254A');
        $this->addSql('DROP INDEX IDX_75B5683FE7A1254A ON campany');
        $this->addSql('ALTER TABLE campany DROP contact_id');
        $this->addSql('ALTER TABLE contact ADD campany_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6385F59C144 FOREIGN KEY (campany_id) REFERENCES campany (id)');
        $this->addSql('CREATE INDEX IDX_4C62E6385F59C144 ON contact (campany_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campany ADD contact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campany ADD CONSTRAINT FK_75B5683FE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_75B5683FE7A1254A ON campany (contact_id)');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6385F59C144');
        $this->addSql('DROP INDEX IDX_4C62E6385F59C144 ON contact');
        $this->addSql('ALTER TABLE contact DROP campany_id');
    }
}
