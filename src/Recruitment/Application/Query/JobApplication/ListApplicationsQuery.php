<?php

declare(strict_types=1);

namespace App\Recruitment\Application\Query\JobApplication;

use App\Shared\Application\Bus\Query;

final class ListApplicationsQuery implements Query
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $jobId = null,
        public readonly ?string $search = null,
    ) {
    }
}
