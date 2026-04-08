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
final class Version20260129130232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $this->getTable('campany');
        if ($table === null || $table->hasColumn('projet_name')) {
            return;
        }

        $this->addSql('ALTER TABLE campany ADD projet_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $table = $this->getTable('campany');
        if ($table === null || !$table->hasColumn('projet_name')) {
            return;
        }

        $this->addSql('ALTER TABLE campany DROP projet_name');
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
