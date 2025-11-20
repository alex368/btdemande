<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251120122754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE funder (id INT AUTO_INCREMENT NOT NULL, campany_name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roadmap (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, user_id INT DEFAULT NULL, date DATE NOT NULL, INDEX IDX_63C3EEF34584665A (product_id), INDEX IDX_63C3EEF3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE roadmap ADD CONSTRAINT FK_63C3EEF34584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE roadmap ADD CONSTRAINT FK_63C3EEF3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE funding_request ADD CONSTRAINT FK_F0D20FDFA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_F0D20FDFA76ED395 ON funding_request (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE roadmap DROP FOREIGN KEY FK_63C3EEF34584665A');
        $this->addSql('ALTER TABLE roadmap DROP FOREIGN KEY FK_63C3EEF3A76ED395');
        $this->addSql('DROP TABLE funder');
        $this->addSql('DROP TABLE roadmap');
        $this->addSql('ALTER TABLE funding_request DROP FOREIGN KEY FK_F0D20FDFA76ED395');
        $this->addSql('DROP INDEX IDX_F0D20FDFA76ED395 ON funding_request');
    }
}
