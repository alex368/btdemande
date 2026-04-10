<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add estimated_amount, expense_type and funding_request link to roadmap';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = array_keys($schemaManager->listTableColumns('roadmap'));

        if (!in_array('estimated_amount', $columns, true)) {
            $this->addSql('ALTER TABLE roadmap ADD estimated_amount INT DEFAULT NULL');
        }

        if (!in_array('expense_type', $columns, true)) {
            $this->addSql('ALTER TABLE roadmap ADD expense_type VARCHAR(255) DEFAULT NULL');
        }

        if (!in_array('funding_request_id', $columns, true)) {
            $this->addSql('ALTER TABLE roadmap ADD funding_request_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE roadmap ADD CONSTRAINT FK_CE2014A87EA4C4F2 FOREIGN KEY (funding_request_id) REFERENCES funding_request (id) ON DELETE SET NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_CE2014A87EA4C4F2 ON roadmap (funding_request_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = array_keys($schemaManager->listTableColumns('roadmap'));

        if (in_array('funding_request_id', $columns, true)) {
            $this->addSql('ALTER TABLE roadmap DROP FOREIGN KEY FK_CE2014A87EA4C4F2');
            $this->addSql('DROP INDEX UNIQ_CE2014A87EA4C4F2 ON roadmap');
            $this->addSql('ALTER TABLE roadmap DROP funding_request_id');
        }

        if (in_array('expense_type', $columns, true)) {
            $this->addSql('ALTER TABLE roadmap DROP expense_type');
        }

        if (in_array('estimated_amount', $columns, true)) {
            $this->addSql('ALTER TABLE roadmap DROP estimated_amount');
        }
    }
}
