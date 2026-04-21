<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create courses table for teacher video courses';
    }

    public function up(Schema $schema): void
    {
        // Drop partially created table from previous failed attempts before recreating it.
        $this->addSql('DROP TABLE IF EXISTS courses');
        $this->addSql('CREATE TABLE courses (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, video_url VARCHAR(1024) NOT NULL, thumbnail_url VARCHAR(1024) DEFAULT NULL, is_published TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_A9A55A69418E912A (teacher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE courses');
    }
}
