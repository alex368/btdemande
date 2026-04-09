<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_session_event table for session/page/action tracking';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist(['user_session_event'])) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS user_session_event (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(128) NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    action_name VARCHAR(255) DEFAULT NULL,
    route_name VARCHAR(255) DEFAULT NULL,
    path VARCHAR(255) NOT NULL,
    method VARCHAR(10) DEFAULT NULL,
    role_snapshot JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent LONGTEXT DEFAULT NULL,
    referrer VARCHAR(255) DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    occurred_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX idx_user_session_event_session (session_id),
    INDEX idx_user_session_event_type (event_type),
    INDEX idx_user_session_event_occurred (occurred_at),
    INDEX idx_user_session_event_route (route_name),
    UNIQUE INDEX uniq_user_session_event_user (user_id),
    INDEX IDX_94A796C1A76ED395 (user_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql('ALTER TABLE user_session_event ADD CONSTRAINT FK_94A796C1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_session_event');
    }
}
