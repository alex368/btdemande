<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128074942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_product ADD quote_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_product ADD CONSTRAINT FK_1CCE5631DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id)');
        $this->addSql('CREATE INDEX IDX_1CCE5631DB805178 ON service_product (quote_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_product DROP FOREIGN KEY FK_1CCE5631DB805178');
        $this->addSql('DROP INDEX IDX_1CCE5631DB805178 ON service_product');
        $this->addSql('ALTER TABLE service_product DROP quote_id');
    }
}
