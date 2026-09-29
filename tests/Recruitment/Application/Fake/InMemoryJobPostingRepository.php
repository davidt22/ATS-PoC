<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Application\Fake;

use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class InMemoryJobPostingRepository implements JobPostingRepositoryInterface
{
    /** @var array<string, JobPosting> */
    private array $jobPostings = [];

    public function save(JobPosting $jobPosting): void
    {
        $this->jobPostings[$jobPosting->id()->value()] = $jobPosting;
    }

    public function delete(JobPosting $jobPosting): void
    {
        unset($this->jobPostings[$jobPosting->id()->value()]);
    }

    public function findAll(): array
    {
        return array_values($this->jobPostings);
    }

    public function findById(Uuid $id): ?JobPosting
    {
        return $this->jobPostings[$id->value()] ?? null;
    }
}
