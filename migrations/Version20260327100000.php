<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add project type to funding mechanism';
    }

    public function up(Schema $schema): void
    {
        $table = $this->getTable('funding_mechanism');
        if ($table === null || $table->hasColumn('project_type')) {
            return;
        }

        $this->addSql('ALTER TABLE funding_mechanism ADD project_type VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $table = $this->getTable('funding_mechanism');
        if ($table === null || !$table->hasColumn('project_type')) {
            return;
        }

        $this->addSql('ALTER TABLE funding_mechanism DROP project_type');
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
