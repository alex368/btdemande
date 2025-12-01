<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128104635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_item ADD product_service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A947E3BF6CD FOREIGN KEY (product_service_id) REFERENCES service_product (id)');
        $this->addSql('CREATE INDEX IDX_8DFC7A947E3BF6CD ON quote_item (product_service_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A947E3BF6CD');
        $this->addSql('DROP INDEX IDX_8DFC7A947E3BF6CD ON quote_item');
        $this->addSql('ALTER TABLE quote_item DROP product_service_id');
    }
}
