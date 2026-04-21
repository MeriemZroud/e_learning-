<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420000500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create AI course quizzes and student quiz submissions tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE course_quizzes (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, teacher_id INT NOT NULL, title VARCHAR(255) NOT NULL, source_summary LONGTEXT DEFAULT NULL, questions_json LONGTEXT NOT NULL, status VARCHAR(40) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D9462EC1591CC992 (course_id), INDEX IDX_D9462EC1418E912A (teacher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course_quiz_submissions (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, student_id INT NOT NULL, answers_json LONGTEXT DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, submitted_at DATETIME DEFAULT NULL, status VARCHAR(40) DEFAULT NULL, INDEX IDX_3EA5AAB6853CD175 (quiz_id), INDEX IDX_3EA5AAB6CB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE course_quiz_submissions');
        $this->addSql('DROP TABLE course_quizzes');
    }
}
