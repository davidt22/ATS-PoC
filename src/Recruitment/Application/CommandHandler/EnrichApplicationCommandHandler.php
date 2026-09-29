<?php

declare(strict_types=1);

namespace App\Recruitment\Application\CommandHandler;

use App\Recruitment\Application\Command\EnrichApplicationCommand;
use App\Recruitment\Domain\Exception\ApplicationNotFoundException;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\ValueObject\AiScore;
use App\Shared\Application\Bus\EventBus;
use App\Shared\Domain\Clock;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class EnrichApplicationCommandHandler
{
    public function __construct(
        private readonly JobApplicationRepositoryInterface $applications,
        private readonly EventBus $eventBus,
        private readonly Clock $clock,
    ) {
    }

    public function __invoke(EnrichApplicationCommand $command): void
    {
        $id = new Uuid($command->applicationId);
        $application = $this->applications->findById($id);

        if (null === $application) {
            throw ApplicationNotFoundException::withId($command->applicationId);
        }

        $application->enrich($command->summary, new AiScore($command->score), $this->clock->now());

        $this->applications->save($application);

        $this->eventBus->publish(...$application->pullDomainEvents());
    }
}
