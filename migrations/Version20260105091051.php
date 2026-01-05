<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260105091051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, activity_date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_AC74095AE7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE add_on_product (id INT AUTO_INCREMENT NOT NULL, quote_item_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, percentage DOUBLE PRECISION NOT NULL, INDEX IDX_B85CE6B7FD80FADA (quote_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campany (id INT AUTO_INCREMENT NOT NULL, legal_name VARCHAR(255) NOT NULL, sector VARCHAR(255) NOT NULL, adress VARCHAR(255) NOT NULL, siren VARCHAR(255) NOT NULL, creation_date DATE NOT NULL, stage VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campany_user (campany_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_9C88E7A15F59C144 (campany_id), INDEX IDX_9C88E7A1A76ED395 (user_id), PRIMARY KEY(campany_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, campany_id INT DEFAULT NULL, account_id INT DEFAULT NULL, salutation VARCHAR(255) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, email LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', phone LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', mobile_phone LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', social_media LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', country VARCHAR(255) DEFAULT NULL, adress VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, occupation VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(255) DEFAULT NULL, INDEX IDX_4C62E6385F59C144 (campany_id), INDEX IDX_4C62E6389B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, document_definition_id INT DEFAULT NULL, funding_request_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, title VARCHAR(255) DEFAULT NULL, status TINYINT(1) NOT NULL, comment LONGTEXT DEFAULT NULL, INDEX IDX_D8698A76F6B10D9A (document_definition_id), INDEX IDX_D8698A76FD118FA (funding_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document_template (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, template VARCHAR(255) DEFAULT NULL, INDEX IDX_18A1EEDA4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE funding_mechanism (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, sector VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, logo VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE funding_request (id INT AUTO_INCREMENT NOT NULL, campany_id INT DEFAULT NULL, product_id INT NOT NULL, user_id INT NOT NULL, amount INT NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_F0D20FDF5F59C144 (campany_id), INDEX IDX_F0D20FDF4584665A (product_id), INDEX IDX_F0D20FDFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE opportunity (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, lead_source VARCHAR(255) NOT NULL, stage VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8389C3D7A76ED395 (user_id), INDEX IDX_8389C3D7E7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, funding_mechanism_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, product_description LONGTEXT NOT NULL, INDEX IDX_D34A04ADDC38C20C (funding_mechanism_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quote (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', quote_number VARCHAR(255) NOT NULL, expiration_date DATE NOT NULL, INDEX IDX_6B71CBF49395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quote_item (id INT AUTO_INCREMENT NOT NULL, quote_id INT DEFAULT NULL, product_service_id INT DEFAULT NULL, INDEX IDX_8DFC7A94DB805178 (quote_id), INDEX IDX_8DFC7A947E3BF6CD (product_service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service_product (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE add_on_product ADD CONSTRAINT FK_B85CE6B7FD80FADA FOREIGN KEY (quote_item_id) REFERENCES quote_item (id)');
        $this->addSql('ALTER TABLE campany_user ADD CONSTRAINT FK_9C88E7A15F59C144 FOREIGN KEY (campany_id) REFERENCES campany (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campany_user ADD CONSTRAINT FK_9C88E7A1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6385F59C144 FOREIGN KEY (campany_id) REFERENCES campany (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6389B6B5FBA FOREIGN KEY (account_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76F6B10D9A FOREIGN KEY (document_definition_id) REFERENCES document_template (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76FD118FA FOREIGN KEY (funding_request_id) REFERENCES funding_request (id)');
        $this->addSql('ALTER TABLE document_template ADD CONSTRAINT FK_18A1EEDA4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE funding_request ADD CONSTRAINT FK_F0D20FDF5F59C144 FOREIGN KEY (campany_id) REFERENCES campany (id)');
        $this->addSql('ALTER TABLE funding_request ADD CONSTRAINT FK_F0D20FDF4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE funding_request ADD CONSTRAINT FK_F0D20FDFA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE opportunity ADD CONSTRAINT FK_8389C3D7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE opportunity ADD CONSTRAINT FK_8389C3D7E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADDC38C20C FOREIGN KEY (funding_mechanism_id) REFERENCES funding_mechanism (id)');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF49395C3F3 FOREIGN KEY (customer_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id)');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A947E3BF6CD FOREIGN KEY (product_service_id) REFERENCES service_product (id)');
        $this->addSql('DROP INDEX IDX_63C3EEF3A76ED395 ON roadmap');
        $this->addSql('ALTER TABLE roadmap CHANGE user_id campany_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE roadmap ADD CONSTRAINT FK_63C3EEF34584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE roadmap ADD CONSTRAINT FK_63C3EEF35F59C144 FOREIGN KEY (campany_id) REFERENCES campany (id)');
        $this->addSql('CREATE INDEX IDX_63C3EEF35F59C144 ON roadmap (campany_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE roadmap DROP FOREIGN KEY FK_63C3EEF35F59C144');
        $this->addSql('ALTER TABLE roadmap DROP FOREIGN KEY FK_63C3EEF34584665A');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AE7A1254A');
        $this->addSql('ALTER TABLE add_on_product DROP FOREIGN KEY FK_B85CE6B7FD80FADA');
        $this->addSql('ALTER TABLE campany_user DROP FOREIGN KEY FK_9C88E7A15F59C144');
        $this->addSql('ALTER TABLE campany_user DROP FOREIGN KEY FK_9C88E7A1A76ED395');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6385F59C144');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6389B6B5FBA');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76F6B10D9A');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76FD118FA');
        $this->addSql('ALTER TABLE document_template DROP FOREIGN KEY FK_18A1EEDA4584665A');
        $this->addSql('ALTER TABLE funding_request DROP FOREIGN KEY FK_F0D20FDF5F59C144');
        $this->addSql('ALTER TABLE funding_request DROP FOREIGN KEY FK_F0D20FDF4584665A');
        $this->addSql('ALTER TABLE funding_request DROP FOREIGN KEY FK_F0D20FDFA76ED395');
        $this->addSql('ALTER TABLE opportunity DROP FOREIGN KEY FK_8389C3D7A76ED395');
        $this->addSql('ALTER TABLE opportunity DROP FOREIGN KEY FK_8389C3D7E7A1254A');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADDC38C20C');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF49395C3F3');
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A94DB805178');
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A947E3BF6CD');
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE add_on_product');
        $this->addSql('DROP TABLE campany');
        $this->addSql('DROP TABLE campany_user');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE document_template');
        $this->addSql('DROP TABLE funding_mechanism');
        $this->addSql('DROP TABLE funding_request');
        $this->addSql('DROP TABLE opportunity');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE quote');
        $this->addSql('DROP TABLE quote_item');
        $this->addSql('DROP TABLE service_product');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP INDEX IDX_63C3EEF35F59C144 ON roadmap');
        $this->addSql('ALTER TABLE roadmap CHANGE campany_id user_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_63C3EEF3A76ED395 ON roadmap (user_id)');
    }
}
