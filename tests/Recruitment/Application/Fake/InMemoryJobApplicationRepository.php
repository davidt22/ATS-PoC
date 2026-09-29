<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Application\Fake;

use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\Repository\ApplicationSearchCriteria;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class InMemoryJobApplicationRepository implements JobApplicationRepositoryInterface
{
    /** @var array<string, JobApplication> */
    private array $applications = [];

    public function save(JobApplication $application): void
    {
        $this->applications[$application->id()->value()] = $application;
    }

    public function findById(Uuid $id): ?JobApplication
    {
        return $this->applications[$id->value()] ?? null;
    }

    public function existsByJobId(Uuid $jobId): bool
    {
        foreach ($this->applications as $application) {
            if ($application->jobId()->equals($jobId)) {
                return true;
            }
        }

        return false;
    }

    public function search(ApplicationSearchCriteria $criteria): array
    {
        return array_values($this->applications);
    }
}
