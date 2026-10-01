<?php

declare(strict_types=1);

namespace App\Recruitment\Application\CommandHandler\JobApplication;

use App\Recruitment\Domain\Exception\DuplicateJobApplicationException;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\ValueObject\CvText;
use App\Recruitment\Domain\ValueObject\Email;
use App\Recruitment\Domain\ValueObject\FullName;
use App\Recruitment\Domain\ValueObject\Phone;
use App\Shared\Application\Bus\EventBus;
use App\Shared\Domain\Clock;
use App\Shared\Domain\ValueObject\Uuid;
use App\Recruitment\Application\Command\JobApplication\SubmitApplicationCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class SubmitApplicationCommandHandler
{
    public function __construct(
        private readonly JobApplicationRepositoryInterface $applicationRepository,
        private readonly JobPostingRepositoryInterface $jobPostingRepository,
        private readonly EventBus $eventBus,
        private readonly Clock $clock,
    ) {
    }

    public function __invoke(SubmitApplicationCommand $command): void
    {
        $jobId = new Uuid($command->jobId);

        $this->checkJobPostingExists($jobId);

        $email = new Email($command->email);

        $this->validateJobApplication($email, $jobId);

        $application = JobApplication::create(
            new Uuid($command->applicationId),
            $jobId,
            new FullName($command->fullName),
            $email,
            null !== $command->phone && '' !== trim($command->phone) ? new Phone($command->phone) : null,
            null !== $command->notes && '' !== trim($command->notes) ? trim($command->notes) : null,
            new CvText($command->cvText),
            $this->clock->now(),
        );

        $this->applicationRepository->save($application);

        $this->eventBus->publish(...$application->pullDomainEvents());
    }

    public function checkJobPostingExists(Uuid $jobId): void
    {
        if (null === $this->jobPostingRepository->findById($jobId)) {
            throw JobPostingNotFoundException::withId($jobId->value());
        }
    }

    public function validateJobApplication(Email $email, Uuid $jobId): void
    {
        if ($this->applicationRepository->existsByEmailAndJobId($email, $jobId)) {
            throw DuplicateJobApplicationException::forEmailAndJobId($email->value(), $jobId->value());
        }
    }
}
