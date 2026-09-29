<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class ApplicationEnriched implements DomainEvent
{
    public function __construct(
        private readonly string $applicationId,
        private readonly int $score,
        private readonly \DateTimeImmutable $occurredOn,
    ) {
    }

    public function applicationId(): string
    {
        return $this->applicationId;
    }

    public function score(): int
    {
        return $this->score;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
