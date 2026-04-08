<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add referent to user and assistant/created_at to funding_request';
    }

    public function up(Schema $schema): void
    {
        $userTable = $this->getTable('user');
        if ($userTable !== null && !$userTable->hasColumn('referent_id')) {
            $this->addSql('ALTER TABLE `user` ADD referent_id INT DEFAULT NULL');
            $userTable = $this->getTable('user');
        }

        $fundingRequestTable = $this->getTable('funding_request');
        if ($fundingRequestTable !== null && !$fundingRequestTable->hasColumn('assistant_id')) {
            $this->addSql('ALTER TABLE funding_request ADD assistant_id INT DEFAULT NULL');
            $fundingRequestTable = $this->getTable('funding_request');
        }

        if ($fundingRequestTable !== null && !$fundingRequestTable->hasColumn('created_at')) {
            $this->addSql("ALTER TABLE funding_request ADD created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            $fundingRequestTable = $this->getTable('funding_request');
        }

        if ($userTable !== null && $userTable->hasColumn('referent_id') && !$this->hasForeignKey($userTable, 'FK_8D93D649A38BC13')) {
            $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649A38BC13 FOREIGN KEY (referent_id) REFERENCES `user` (id)');
            $userTable = $this->getTable('user');
        }

        if ($fundingRequestTable !== null && $fundingRequestTable->hasColumn('assistant_id') && !$this->hasForeignKey($fundingRequestTable, 'FK_F0D20FDFCC73527F')) {
            $this->addSql('ALTER TABLE funding_request ADD CONSTRAINT FK_F0D20FDFCC73527F FOREIGN KEY (assistant_id) REFERENCES `user` (id)');
            $fundingRequestTable = $this->getTable('funding_request');
        }

        if ($userTable !== null && $userTable->hasColumn('referent_id') && !$userTable->hasIndex('IDX_8D93D649A38BC13')) {
            $this->addSql('CREATE INDEX IDX_8D93D649A38BC13 ON `user` (referent_id)');
        }

        if ($fundingRequestTable !== null && $fundingRequestTable->hasColumn('assistant_id') && !$fundingRequestTable->hasIndex('IDX_F0D20FDFCC73527F')) {
            $this->addSql('CREATE INDEX IDX_F0D20FDFCC73527F ON funding_request (assistant_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $fundingRequestTable = $this->getTable('funding_request');
        $userTable = $this->getTable('user');

        if ($fundingRequestTable !== null && $this->hasForeignKey($fundingRequestTable, 'FK_F0D20FDFCC73527F')) {
            $this->addSql('ALTER TABLE funding_request DROP FOREIGN KEY FK_F0D20FDFCC73527F');
            $fundingRequestTable = $this->getTable('funding_request');
        }

        if ($userTable !== null && $this->hasForeignKey($userTable, 'FK_8D93D649A38BC13')) {
            $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649A38BC13');
            $userTable = $this->getTable('user');
        }

        if ($fundingRequestTable !== null && $fundingRequestTable->hasIndex('IDX_F0D20FDFCC73527F')) {
            $this->addSql('DROP INDEX IDX_F0D20FDFCC73527F ON funding_request');
            $fundingRequestTable = $this->getTable('funding_request');
        }

        if ($userTable !== null && $userTable->hasIndex('IDX_8D93D649A38BC13')) {
            $this->addSql('DROP INDEX IDX_8D93D649A38BC13 ON `user`');
            $userTable = $this->getTable('user');
        }

        if ($fundingRequestTable !== null && $fundingRequestTable->hasColumn('assistant_id')) {
            $this->addSql('ALTER TABLE funding_request DROP assistant_id');
            $fundingRequestTable = $this->getTable('funding_request');
        }

        if ($fundingRequestTable !== null && $fundingRequestTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE funding_request DROP created_at');
        }

        if ($userTable !== null && $userTable->hasColumn('referent_id')) {
            $this->addSql('ALTER TABLE `user` DROP referent_id');
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
