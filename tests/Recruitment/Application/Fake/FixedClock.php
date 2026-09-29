<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Application\Fake;

use App\Shared\Domain\Clock;

final class FixedClock implements Clock
{
    public function __construct(
        private readonly \DateTimeImmutable $now,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
