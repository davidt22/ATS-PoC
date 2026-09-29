<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class ApplicationSubmitted implements DomainEvent
{
    public function __construct(
        private readonly string $applicationId,
        private readonly string $jobId,
        private readonly string $cvText,
        private readonly \DateTimeImmutable $occurredOn,
    ) {
    }

    public function applicationId(): string
    {
        return $this->applicationId;
    }

    public function jobId(): string
    {
        return $this->jobId;
    }

    public function cvText(): string
    {
        return $this->cvText;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
