<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129090231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $this->getTable('product');
        if ($table === null || $table->hasColumn('type_product')) {
            return;
        }

        $this->addSql('ALTER TABLE product ADD type_product VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $table = $this->getTable('product');
        if ($table === null || !$table->hasColumn('type_product')) {
            return;
        }

        $this->addSql('ALTER TABLE product DROP type_product');
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
