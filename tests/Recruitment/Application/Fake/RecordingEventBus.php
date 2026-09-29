<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Application\Fake;

use App\Shared\Application\Bus\EventBus;
use App\Shared\Domain\Event\DomainEvent;

final class RecordingEventBus implements EventBus
{
    /** @var list<DomainEvent> */
    private array $published = [];

    public function publish(DomainEvent ...$events): void
    {
        array_push($this->published, ...$events);
    }

    /** @return list<DomainEvent> */
    public function published(): array
    {
        return $this->published;
    }
}
