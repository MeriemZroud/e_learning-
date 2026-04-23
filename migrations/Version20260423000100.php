<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop notifications table and create notification table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS notifications');

        $tableExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'notification'");
        if ($tableExists === 0) {
            $this->addSql("CREATE TABLE notification (id BIGINT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(50) DEFAULT 'info' NOT NULL, is_read TINYINT(1) DEFAULT 0 NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }

        $fkExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'FK_BF5476CAA76ED395'");
        if ($fkExists === 0) {
            $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS notification');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, class_id INT DEFAULT NULL, created_by INT DEFAULT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_6000B0D2A8F2C8BF (class_id), INDEX IDX_6000B0D2DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D2A8F2C8BF FOREIGN KEY (class_id) REFERENCES classes (id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D2DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
    }
}
