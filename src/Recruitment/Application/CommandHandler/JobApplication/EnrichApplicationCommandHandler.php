<?php

declare(strict_types=1);

namespace App\Recruitment\Application\CommandHandler\JobApplication;

use App\Recruitment\Domain\Exception\ApplicationNotFoundException;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\ValueObject\AiScore;
use App\Shared\Application\Bus\EventBus;
use App\Shared\Domain\Clock;
use App\Shared\Domain\ValueObject\Uuid;
use App\Recruitment\Application\Command\JobApplication\EnrichApplicationCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class EnrichApplicationCommandHandler
{
    public function __construct(
        private readonly JobApplicationRepositoryInterface $applicationRepository,
        private readonly EventBus $eventBus,
        private readonly Clock $clock,
    ) {
    }

    public function __invoke(EnrichApplicationCommand $command): void
    {
        $id = new Uuid($command->applicationId);
        $application = $this->applicationRepository->findById($id);

        if (null === $application) {
            throw ApplicationNotFoundException::withId($command->applicationId);
        }

        $application->enrich($command->summary, new AiScore($command->score), $this->clock->now());

        $this->applicationRepository->save($application);

        $this->eventBus->publish(...$application->pullDomainEvents());
    }
}
