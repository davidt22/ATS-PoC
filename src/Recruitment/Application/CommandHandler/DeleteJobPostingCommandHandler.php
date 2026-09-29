<?php

declare(strict_types=1);

namespace App\Recruitment\Application\CommandHandler;

use App\Recruitment\Application\Command\DeleteJobPostingCommand;
use App\Recruitment\Domain\Exception\JobPostingHasApplicationsException;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteJobPostingCommandHandler
{
    public function __construct(
        private readonly JobPostingRepositoryInterface $jobPostings,
        private readonly JobApplicationRepositoryInterface $applications,
    ) {
    }

    public function __invoke(DeleteJobPostingCommand $command): void
    {
        $id = new Uuid($command->jobPostingId);
        $jobPosting = $this->jobPostings->findById($id);

        if (null === $jobPosting) {
            throw JobPostingNotFoundException::withId($command->jobPostingId);
        }

        if ($this->applications->existsByJobId($id)) {
            throw JobPostingHasApplicationsException::forId($command->jobPostingId);
        }

        $this->jobPostings->delete($jobPosting);
    }
}
