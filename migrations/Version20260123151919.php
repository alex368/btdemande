<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123151919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE partnership DROP FOREIGN KEY FK_8619D6AE6CC88588');
        $this->addSql('DROP INDEX IDX_8619D6AE6CC88588 ON partnership');
        $this->addSql('ALTER TABLE partnership CHANGE funder_id funding_mechanism_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE partnership ADD CONSTRAINT FK_8619D6AEDC38C20C FOREIGN KEY (funding_mechanism_id) REFERENCES funding_mechanism (id)');
        $this->addSql('CREATE INDEX IDX_8619D6AEDC38C20C ON partnership (funding_mechanism_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE partnership DROP FOREIGN KEY FK_8619D6AEDC38C20C');
        $this->addSql('DROP INDEX IDX_8619D6AEDC38C20C ON partnership');
        $this->addSql('ALTER TABLE partnership CHANGE funding_mechanism_id funder_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE partnership ADD CONSTRAINT FK_8619D6AE6CC88588 FOREIGN KEY (funder_id) REFERENCES funder (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8619D6AE6CC88588 ON partnership (funder_id)');
    }
}
