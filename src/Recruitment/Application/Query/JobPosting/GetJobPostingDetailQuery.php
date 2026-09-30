<?php

declare(strict_types=1);

namespace App\Recruitment\Application\Query\JobPosting;

use App\Shared\Application\Bus\Query;

final class GetJobPostingDetailQuery implements Query
{
    public function __construct(
        public readonly string $jobPostingId,
    ) {
    }
}
