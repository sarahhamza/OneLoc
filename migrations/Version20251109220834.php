<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109220834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles JSON column to users table and convert existing string roles to array';
    }

    public function up(Schema $schema): void
    {
        // 1. Add roles column as JSON (not null, default empty array)
        $this->addSql('ALTER TABLE users ADD roles JSON NOT NULL DEFAULT "[]"');

        // 2. Fix old data: Convert string roles (e.g. "ROLE_USER") → ["ROLE_USER"]
        $this->addSql("
            UPDATE users 
            SET roles = CASE
                WHEN roles IS NULL OR roles = '' THEN '[\"ROLE_USER\"]'
                WHEN roles LIKE 'ROLE_%' THEN CONCAT('[\"', roles, '\"]')
                ELSE '[\"ROLE_USER\"]'
            END
        ");

        // 3. Ensure all users have at least ROLE_USER
        $this->addSql("UPDATE users SET roles = '[\"ROLE_USER\"]' WHERE roles = '[]' OR roles IS NULL");
    }

    public function down(Schema $schema): void
    {
        // Optional: Convert back to string on rollback (for safety)
        $this->addSql("
            UPDATE users 
            SET roles = CASE
                WHEN JSON_CONTAINS(roles, '\"ROLE_USER\"') THEN 'ROLE_USER'
                WHEN JSON_CONTAINS(roles, '\"ROLE_ADMIN\"') THEN 'ROLE_ADMIN'
                ELSE ''
            END
        ");

        // Drop the column
        $this->addSql('ALTER TABLE users DROP roles');
    }
}