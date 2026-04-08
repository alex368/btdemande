<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add location fields to funding mechanism';
    }

    public function up(Schema $schema): void
    {
        $table = $this->getTable('funding_mechanism');
        if ($table === null) {
            return;
        }

        if (!$table->hasColumn('country')) {
            $this->addSql('ALTER TABLE funding_mechanism ADD country VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('region')) {
            $this->addSql('ALTER TABLE funding_mechanism ADD region VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('city')) {
            $this->addSql('ALTER TABLE funding_mechanism ADD city VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('address')) {
            $this->addSql('ALTER TABLE funding_mechanism ADD address VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('postal_code')) {
            $this->addSql('ALTER TABLE funding_mechanism ADD postal_code VARCHAR(50) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $this->getTable('funding_mechanism');
        if ($table === null) {
            return;
        }

        if ($table->hasColumn('country')) {
            $this->addSql('ALTER TABLE funding_mechanism DROP country');
        }

        if ($table->hasColumn('region')) {
            $this->addSql('ALTER TABLE funding_mechanism DROP region');
        }

        if ($table->hasColumn('city')) {
            $this->addSql('ALTER TABLE funding_mechanism DROP city');
        }

        if ($table->hasColumn('address')) {
            $this->addSql('ALTER TABLE funding_mechanism DROP address');
        }

        if ($table->hasColumn('postal_code')) {
            $this->addSql('ALTER TABLE funding_mechanism DROP postal_code');
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
