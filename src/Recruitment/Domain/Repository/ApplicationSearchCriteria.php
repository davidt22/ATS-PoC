<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Repository;

use App\Recruitment\Domain\ValueObject\ApplicationStatus;

final class ApplicationSearchCriteria
{
    public function __construct(
        public readonly ?ApplicationStatus $status = null,
        public readonly ?string $jobId = null,
        public readonly ?string $search = null,
    ) {
    }
}
