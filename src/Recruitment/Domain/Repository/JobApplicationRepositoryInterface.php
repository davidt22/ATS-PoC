<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Repository;

use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

interface JobApplicationRepositoryInterface
{
    public function save(JobApplication $application): void;

    public function findById(Uuid $id): ?JobApplication;

    public function existsByJobId(Uuid $jobId): bool;

    public function existsByEmailAndJobId(Email $email, Uuid $jobId): bool;

    /**
     * @return list<JobApplication>
     */
    public function search(ApplicationSearchCriteria $criteria): array;
}
