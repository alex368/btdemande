<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224153336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$this->getSchemaManager()->tablesExist(['campany_contact'])) {
            $this->addSql('CREATE TABLE campany_contact (id INT AUTO_INCREMENT NOT NULL, legal_name VARCHAR(255) DEFAULT NULL, sector VARCHAR(255) DEFAULT NULL, adress VARCHAR(255) DEFAULT NULL, siren VARCHAR(255) DEFAULT NULL, creation_date DATE DEFAULT NULL, stage VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, project_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        $table = $this->getTable('partnership');
        if ($table !== null && $table->hasColumn('funder_id')) {
            $this->addSql('ALTER TABLE partnership DROP funder_id');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->getSchemaManager()->tablesExist(['campany_contact'])) {
            $this->addSql('DROP TABLE campany_contact');
        }

        $table = $this->getTable('partnership');
        if ($table !== null && !$table->hasColumn('funder_id')) {
            $this->addSql('ALTER TABLE partnership ADD funder_id INT DEFAULT NULL');
        }
    }

    private function getTable(string $tableName): ?Table
    {
        $schemaManager = $this->getSchemaManager();
        if (!$schemaManager->tablesExist([$tableName])) {
            return null;
        }

        return $schemaManager->introspectTable($tableName);
    }

    private function getSchemaManager(): AbstractSchemaManager
    {
        return $this->connection->createSchemaManager();
    }
}
