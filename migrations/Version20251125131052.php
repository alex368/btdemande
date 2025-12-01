<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125131052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, activity_date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_AC74095AE7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, salutation VARCHAR(255) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, email LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', phone LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', mobile_phone LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE opportunity (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, lead_source VARCHAR(255) NOT NULL, stage VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8389C3D7A76ED395 (user_id), INDEX IDX_8389C3D7E7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE opportunity ADD CONSTRAINT FK_8389C3D7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE opportunity ADD CONSTRAINT FK_8389C3D7E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE campany ADD contact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campany ADD CONSTRAINT FK_75B5683FE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_75B5683FE7A1254A ON campany (contact_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campany DROP FOREIGN KEY FK_75B5683FE7A1254A');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AE7A1254A');
        $this->addSql('ALTER TABLE opportunity DROP FOREIGN KEY FK_8389C3D7A76ED395');
        $this->addSql('ALTER TABLE opportunity DROP FOREIGN KEY FK_8389C3D7E7A1254A');
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE opportunity');
        $this->addSql('DROP INDEX IDX_75B5683FE7A1254A ON campany');
        $this->addSql('ALTER TABLE campany DROP contact_id');
    }
}
