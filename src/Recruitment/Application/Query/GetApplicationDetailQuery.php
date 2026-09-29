<?php

declare(strict_types=1);

namespace App\Recruitment\Application\Query;

use App\Shared\Application\Bus\Query;

final class GetApplicationDetailQuery implements Query
{
    public function __construct(
        public readonly string $applicationId,
    ) {
    }
}
