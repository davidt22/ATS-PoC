<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\UI\Http;

use Doctrine\ORM\EntityManagerInterface;

/**
 * WebTestCase requests go through a real HTTP kernel cycle, which resets
 * Doctrine's connection between requests — a manually-opened transaction
 * does not survive it. Cleaning the tables directly keeps each test
 * isolated without relying on transactions.
 */
trait CleansRecruitmentTables
{
    protected function cleanRecruitmentTables(): void
    {
        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $connection->executeStatement('DELETE FROM job_applications');
        $connection->executeStatement('DELETE FROM job_postings');
    }
}
