<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420000400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional pdf_file column to courses table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE courses ADD pdf_file VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE courses DROP pdf_file');
    }
}
