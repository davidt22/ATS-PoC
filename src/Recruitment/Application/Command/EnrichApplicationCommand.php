<?php

declare(strict_types=1);

namespace App\Recruitment\Application\Command;

use App\Shared\Application\Bus\Command;

final class EnrichApplicationCommand implements Command
{
    public function __construct(
        public readonly string $applicationId,
        public readonly string $summary,
        public readonly int $score,
    ) {
    }
}
