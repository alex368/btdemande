<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224152143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campany_contact (id INT AUTO_INCREMENT NOT NULL, legal_name VARCHAR(255) DEFAULT NULL, sector VARCHAR(255) DEFAULT NULL, adress VARCHAR(255) DEFAULT NULL, siren VARCHAR(255) DEFAULT NULL, creation_date DATE DEFAULT NULL, stage VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, project_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document_rag_chunk (id INT AUTO_INCREMENT NOT NULL, document_id INT NOT NULL, chunk_index INT NOT NULL, content LONGTEXT NOT NULL, embedding JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', page_number INT DEFAULT NULL, INDEX IDX_D6C83903C33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document_rag_index (id INT AUTO_INCREMENT NOT NULL, document_id INT NOT NULL, content_hash VARCHAR(64) NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C3BD2F2CC33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE document_rag_chunk ADD CONSTRAINT FK_D6C83903C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_rag_index ADD CONSTRAINT FK_C3BD2F2CC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_rag_chunk DROP FOREIGN KEY FK_D6C83903C33F7837');
        $this->addSql('ALTER TABLE document_rag_index DROP FOREIGN KEY FK_C3BD2F2CC33F7837');
        $this->addSql('DROP TABLE campany_contact');
        $this->addSql('DROP TABLE document_rag_chunk');
        $this->addSql('DROP TABLE document_rag_index');
    }
}
