<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121154211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('ALTER TABLE roadmap DROP FOREIGN KEY FK_63C3EEF3A76ED395');
        // $this->addSql('DROP INDEX IDX_63C3EEF3A76ED395 ON roadmap');
        // $this->addSql('ALTER TABLE roadmap CHANGE user_id campany_id INT DEFAULT NULL');
        // $this->addSql('ALTER TABLE roadmap ADD CONSTRAINT FK_63C3EEF35F59C144 FOREIGN KEY (campany_id) REFERENCES campany (id)');
        // $this->addSql('CREATE INDEX IDX_63C3EEF35F59C144 ON roadmap (campany_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // $this->addSql('ALTER TABLE roadmap DROP FOREIGN KEY FK_63C3EEF35F59C144');
        // $this->addSql('DROP INDEX IDX_63C3EEF35F59C144 ON roadmap');
        // $this->addSql('ALTER TABLE roadmap CHANGE campany_id user_id INT DEFAULT NULL');
        // $this->addSql('ALTER TABLE roadmap ADD CONSTRAINT FK_63C3EEF3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        // $this->addSql('CREATE INDEX IDX_63C3EEF3A76ED395 ON roadmap (user_id)');
    }
}
