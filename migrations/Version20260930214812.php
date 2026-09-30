<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930214812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint on (email, job_id) to prevent duplicate applications for the same job posting.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications ADD UNIQUE INDEX uniq_job_applications_email_job_id (email, job_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications DROP INDEX uniq_job_applications_email_job_id');
    }
}
