<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225120803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->getSchemaManager()->tablesExist(['campany_contact', 'campany_contact_contact', 'document_rag_chunk', 'document_rag_index'])) {
            return;
        }

        $this->addSql('CREATE TABLE campany_contact (id INT AUTO_INCREMENT NOT NULL, legal_name VARCHAR(255) DEFAULT NULL, sector VARCHAR(255) DEFAULT NULL, adress VARCHAR(255) DEFAULT NULL, siren VARCHAR(255) DEFAULT NULL, creation_date DATE DEFAULT NULL, stage VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, project_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campany_contact_contact (campany_contact_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_520E81441B0B344A (campany_contact_id), INDEX IDX_520E8144E7A1254A (contact_id), PRIMARY KEY(campany_contact_id, contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document_rag_chunk (id INT AUTO_INCREMENT NOT NULL, document_id INT NOT NULL, chunk_index INT NOT NULL, content LONGTEXT NOT NULL, embedding JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', page_number INT DEFAULT NULL, INDEX IDX_D6C83903C33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document_rag_index (id INT AUTO_INCREMENT NOT NULL, document_id INT NOT NULL, content_hash VARCHAR(64) NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C3BD2F2CC33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campany_contact_contact ADD CONSTRAINT FK_520E81441B0B344A FOREIGN KEY (campany_contact_id) REFERENCES campany_contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campany_contact_contact ADD CONSTRAINT FK_520E8144E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_rag_chunk ADD CONSTRAINT FK_D6C83903C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_rag_index ADD CONSTRAINT FK_C3BD2F2CC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        if (!$this->getSchemaManager()->tablesExist(['campany_contact'])) {
            return;
        }

        $this->addSql('ALTER TABLE campany_contact_contact DROP FOREIGN KEY FK_520E81441B0B344A');
        $this->addSql('ALTER TABLE campany_contact_contact DROP FOREIGN KEY FK_520E8144E7A1254A');
        $this->addSql('ALTER TABLE document_rag_chunk DROP FOREIGN KEY FK_D6C83903C33F7837');
        $this->addSql('ALTER TABLE document_rag_index DROP FOREIGN KEY FK_C3BD2F2CC33F7837');
        $this->addSql('DROP TABLE campany_contact');
        $this->addSql('DROP TABLE campany_contact_contact');
        $this->addSql('DROP TABLE document_rag_chunk');
        $this->addSql('DROP TABLE document_rag_index');
    }

    private function getSchemaManager(): AbstractSchemaManager
    {
        return $this->connection->createSchemaManager();
    }
}
