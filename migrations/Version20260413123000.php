<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Populate campany legal_type automatically from siren values';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE campany
            SET legal_type = CASE
                WHEN TRIM(COALESCE(siren, '')) REGEXP '^0+$' AND TRIM(COALESCE(siren, '')) <> '' THEN 'personne_physique'
                ELSE 'personne_morale'
            END
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE campany SET legal_type = NULL');
    }
}
