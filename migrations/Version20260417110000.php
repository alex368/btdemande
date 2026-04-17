<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active flag on user to allow admin activation and deactivation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('UPDATE `user` SET is_active = 1 WHERE is_active IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP is_active');
    }
}
