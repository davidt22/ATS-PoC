<?php

declare(strict_types=1);

namespace App\Recruitment\Application\Command\JobPosting;

use App\Shared\Application\Bus\Command;

final class CreateJobPostingCommand implements Command
{
    public function __construct(
        public readonly string $jobPostingId,
        public readonly string $title,
        public readonly string $description,
    ) {
    }
}
