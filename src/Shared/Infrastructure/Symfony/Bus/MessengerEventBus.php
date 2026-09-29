<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Bus;

use App\Shared\Application\Bus\EventBus;
use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventBus implements EventBus
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
