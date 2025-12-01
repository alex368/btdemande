<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128105821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE add_on_product (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quote_item ADD add_on_product_id INT DEFAULT NULL, DROP addon_products');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94CCA964A8 FOREIGN KEY (add_on_product_id) REFERENCES add_on_product (id)');
        $this->addSql('CREATE INDEX IDX_8DFC7A94CCA964A8 ON quote_item (add_on_product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A94CCA964A8');
        $this->addSql('DROP TABLE add_on_product');
        $this->addSql('DROP INDEX IDX_8DFC7A94CCA964A8 ON quote_item');
        $this->addSql('ALTER TABLE quote_item ADD addon_products LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', DROP add_on_product_id');
    }
}
