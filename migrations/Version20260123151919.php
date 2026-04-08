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
final class Version20260123151919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $this->getTable('partnership');
        if ($table === null) {
            return;
        }

        if ($this->hasForeignKey($table, 'FK_8619D6AE6CC88588')) {
            $this->addSql('ALTER TABLE partnership DROP FOREIGN KEY FK_8619D6AE6CC88588');
            $table = $this->getTable('partnership');
        }

        if ($table !== null && $table->hasIndex('IDX_8619D6AE6CC88588')) {
            $this->addSql('DROP INDEX IDX_8619D6AE6CC88588 ON partnership');
            $table = $this->getTable('partnership');
        }

        if ($table !== null && $table->hasColumn('funder_id') && !$table->hasColumn('funding_mechanism_id')) {
            $this->addSql('ALTER TABLE partnership CHANGE funder_id funding_mechanism_id INT DEFAULT NULL');
            $table = $this->getTable('partnership');
        }

        if ($table !== null && $table->hasColumn('funding_mechanism_id') && !$this->hasForeignKey($table, 'FK_8619D6AEDC38C20C')) {
            $this->addSql('ALTER TABLE partnership ADD CONSTRAINT FK_8619D6AEDC38C20C FOREIGN KEY (funding_mechanism_id) REFERENCES funding_mechanism (id)');
            $table = $this->getTable('partnership');
        }

        if ($table !== null && $table->hasColumn('funding_mechanism_id') && !$table->hasIndex('IDX_8619D6AEDC38C20C')) {
            $this->addSql('CREATE INDEX IDX_8619D6AEDC38C20C ON partnership (funding_mechanism_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $this->getTable('partnership');
        if ($table === null) {
            return;
        }

        if ($this->hasForeignKey($table, 'FK_8619D6AEDC38C20C')) {
            $this->addSql('ALTER TABLE partnership DROP FOREIGN KEY FK_8619D6AEDC38C20C');
            $table = $this->getTable('partnership');
        }

        if ($table !== null && $table->hasIndex('IDX_8619D6AEDC38C20C')) {
            $this->addSql('DROP INDEX IDX_8619D6AEDC38C20C ON partnership');
            $table = $this->getTable('partnership');
        }

        if ($table !== null && $table->hasColumn('funding_mechanism_id') && !$table->hasColumn('funder_id')) {
            $this->addSql('ALTER TABLE partnership CHANGE funding_mechanism_id funder_id INT DEFAULT NULL');
            $table = $this->getTable('partnership');
        }

        if ($table !== null && $table->hasColumn('funder_id') && !$this->hasForeignKey($table, 'FK_8619D6AE6CC88588')) {
            $this->addSql('ALTER TABLE partnership ADD CONSTRAINT FK_8619D6AE6CC88588 FOREIGN KEY (funder_id) REFERENCES funder (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
            $table = $this->getTable('partnership');
        }

        if ($table !== null && $table->hasColumn('funder_id') && !$table->hasIndex('IDX_8619D6AE6CC88588')) {
            $this->addSql('CREATE INDEX IDX_8619D6AE6CC88588 ON partnership (funder_id)');
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

    private function hasForeignKey(Table $table, string $foreignKeyName): bool
    {
        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getName() === $foreignKeyName) {
                return true;
            }
        }

        return false;
    }

    private function getSchemaManager(): AbstractSchemaManager
    {
        return $this->connection->createSchemaManager();
    }
}
