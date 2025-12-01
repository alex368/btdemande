<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128083723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quote_item (id INT AUTO_INCREMENT NOT NULL, quote_id INT DEFAULT NULL, INDEX IDX_8DFC7A94DB805178 (quote_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id)');
        $this->addSql('ALTER TABLE service_product ADD quote_item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_product ADD CONSTRAINT FK_1CCE5631FD80FADA FOREIGN KEY (quote_item_id) REFERENCES quote_item (id)');
        $this->addSql('CREATE INDEX IDX_1CCE5631FD80FADA ON service_product (quote_item_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_product DROP FOREIGN KEY FK_1CCE5631FD80FADA');
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A94DB805178');
        $this->addSql('DROP TABLE quote_item');
        $this->addSql('DROP INDEX IDX_1CCE5631FD80FADA ON service_product');
        $this->addSql('ALTER TABLE service_product DROP quote_item_id');
    }
}
