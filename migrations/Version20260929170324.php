<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929170324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_applications (job_id VARCHAR(36) NOT NULL, full_name VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, cv_text LONGTEXT NOT NULL, applied_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, ai_summary LONGTEXT DEFAULT NULL, ai_score INT DEFAULT NULL, updated_at DATETIME NOT NULL, id VARCHAR(36) NOT NULL, INDEX idx_job_applications_status (status), INDEX idx_job_applications_job_id (job_id), INDEX idx_job_applications_applied_at (applied_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_postings (title VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE job_applications');
        $this->addSql('DROP TABLE job_postings');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
