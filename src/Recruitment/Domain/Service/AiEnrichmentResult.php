<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Service;

final class AiEnrichmentResult
{
    public function __construct(
        public readonly string $summary,
        public readonly int $score,
    ) {
    }
}
