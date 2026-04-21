<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reset password token and expiration columns to users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD reset_password_token VARCHAR(64) DEFAULT NULL, ADD reset_password_expires_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_USERS_RESET_PASSWORD_TOKEN ON users (reset_password_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_USERS_RESET_PASSWORD_TOKEN ON users');
        $this->addSql('ALTER TABLE users DROP reset_password_token, DROP reset_password_expires_at');
    }
}
