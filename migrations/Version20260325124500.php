<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create API keys table for personal bearer authentication';
    }

    public function up(Schema $schema): void
    {
        if ($this->getSchemaManager()->tablesExist(['api_key'])) {
            return;
        }

        $this->addSql('CREATE TABLE api_key (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, token_hash VARCHAR(64) NOT NULL, token_prefix VARCHAR(16) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', revoked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_91BB0F7A7A4F53DC (token_hash), INDEX IDX_91BB0F7AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_key ADD CONSTRAINT FK_91BB0F7AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $table = $this->getTable('api_key');
        if ($table === null) {
            return;
        }

        if ($this->hasForeignKey($table, 'FK_91BB0F7AA76ED395')) {
            $this->addSql('ALTER TABLE api_key DROP FOREIGN KEY FK_91BB0F7AA76ED395');
        }
        $this->addSql('DROP TABLE api_key');
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
