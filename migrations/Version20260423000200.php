<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow IN_PROGRESS, RESOLVED and REJECTED statuses for reclamations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE reclamations SET status = 'IN_PROGRESS' WHERE status = 'PENDING'");
        $this->addSql("ALTER TABLE reclamations MODIFY status ENUM('IN_PROGRESS', 'RESOLVED', 'REJECTED') DEFAULT 'IN_PROGRESS'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE reclamations SET status = 'IN_PROGRESS' WHERE status = 'REJECTED'");
        $this->addSql("UPDATE reclamations SET status = 'PENDING' WHERE status = 'IN_PROGRESS'");
        $this->addSql("ALTER TABLE reclamations MODIFY status ENUM('PENDING', 'RESOLVED') DEFAULT 'PENDING'");
    }
}
