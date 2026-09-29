<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Repository;

use App\Recruitment\Domain\Model\JobPosting;
use App\Shared\Domain\ValueObject\Uuid;

interface JobPostingRepositoryInterface
{
    public function save(JobPosting $jobPosting): void;

    public function delete(JobPosting $jobPosting): void;

    /**
     * @return list<JobPosting>
     */
    public function findAll(): array;

    public function findById(Uuid $id): ?JobPosting;
}
