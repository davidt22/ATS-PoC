<?php

declare(strict_types=1);

namespace App\Recruitment\Application\EventHandler;

use App\Recruitment\Application\Command\JobApplication\EnrichApplicationCommand;
use App\Recruitment\Domain\Event\ApplicationSubmitted;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\Service\AiEnrichmentPort;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Consumed asynchronously (see messenger.yaml routing for ApplicationSubmitted).
 * Bridges the domain event to the AI enrichment port and, once done, feeds the
 * result back into the aggregate through EnrichApplicationCommand.
 */
#[AsMessageHandler(bus: 'event.bus')]
final class EnrichApplicationOnApplicationSubmitted
{
    public function __construct(
        private readonly JobPostingRepositoryInterface $jobPostingRepository,
        private readonly AiEnrichmentPort $aiEnrichment,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(ApplicationSubmitted $event): void
    {
        $jobPosting = $this->jobPostingRepository->findById(new Uuid($event->jobId()));

        if (null === $jobPosting) {
            throw JobPostingNotFoundException::withId($event->jobId());
        }

        $result = $this->aiEnrichment->enrich($jobPosting->title()->value(), $jobPosting->description()->value(), $event->cvText());

        $this->commandBus->dispatch(new EnrichApplicationCommand(
            $event->applicationId(),
            $result->summary,
            $result->score,
        ));
    }
}
