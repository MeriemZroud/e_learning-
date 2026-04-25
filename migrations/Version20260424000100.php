<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI priority fields to reclamations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE reclamations ADD priority VARCHAR(20) NOT NULL DEFAULT 'NORMAL'");
        $this->addSql('ALTER TABLE reclamations ADD priority_score INT NOT NULL DEFAULT 50');
        $this->addSql('CREATE INDEX IDX_RECLAMATIONS_PRIORITY_SCORE ON reclamations (priority_score)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_RECLAMATIONS_PRIORITY_SCORE ON reclamations');
        $this->addSql('ALTER TABLE reclamations DROP priority_score');
        $this->addSql('ALTER TABLE reclamations DROP priority');
    }
}