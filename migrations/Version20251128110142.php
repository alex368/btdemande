<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128110142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE add_on_product ADD quote_item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE add_on_product ADD CONSTRAINT FK_B85CE6B7FD80FADA FOREIGN KEY (quote_item_id) REFERENCES quote_item (id)');
        $this->addSql('CREATE INDEX IDX_B85CE6B7FD80FADA ON add_on_product (quote_item_id)');
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A94CCA964A8');
        $this->addSql('DROP INDEX IDX_8DFC7A94CCA964A8 ON quote_item');
        $this->addSql('ALTER TABLE quote_item DROP add_on_product_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE add_on_product DROP FOREIGN KEY FK_B85CE6B7FD80FADA');
        $this->addSql('DROP INDEX IDX_B85CE6B7FD80FADA ON add_on_product');
        $this->addSql('ALTER TABLE add_on_product DROP quote_item_id');
        $this->addSql('ALTER TABLE quote_item ADD add_on_product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94CCA964A8 FOREIGN KEY (add_on_product_id) REFERENCES add_on_product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8DFC7A94CCA964A8 ON quote_item (add_on_product_id)');
    }
}
