<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add persistent position field for roadmap ordering';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roadmap ADD position INT DEFAULT NULL');
        $this->addSql('SET @roadmap_position := 0');
        $this->addSql('UPDATE roadmap SET position = (@roadmap_position := @roadmap_position + 1) ORDER BY campany_id ASC, date ASC, id ASC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roadmap DROP position');
    }
}
