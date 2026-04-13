<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add legal type and optional address fields to campany';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE campany ADD city VARCHAR(255) DEFAULT NULL, ADD zip_code VARCHAR(32) DEFAULT NULL, ADD country VARCHAR(255) DEFAULT NULL, ADD legal_type VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE campany DROP city, DROP zip_code, DROP country, DROP legal_type');
    }
}
