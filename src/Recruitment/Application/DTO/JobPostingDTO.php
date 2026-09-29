<?php

declare(strict_types=1);

namespace App\Recruitment\Application\DTO;

use App\Recruitment\Domain\Model\JobPosting;

final class JobPostingDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
    ) {
    }

    public static function fromDomain(JobPosting $jobPosting): self
    {
        return new self($jobPosting->id()->value(), $jobPosting->title()->value(), $jobPosting->description()->value());
    }
}
