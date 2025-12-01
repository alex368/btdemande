<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128103409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_item ADD addon_products LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE service_product DROP FOREIGN KEY FK_1CCE5631FD80FADA');
        $this->addSql('DROP INDEX IDX_1CCE5631FD80FADA ON service_product');
        $this->addSql('ALTER TABLE service_product DROP quote_item_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_item DROP addon_products');
        $this->addSql('ALTER TABLE service_product ADD quote_item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_product ADD CONSTRAINT FK_1CCE5631FD80FADA FOREIGN KEY (quote_item_id) REFERENCES quote_item (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_1CCE5631FD80FADA ON service_product (quote_item_id)');
    }
}
