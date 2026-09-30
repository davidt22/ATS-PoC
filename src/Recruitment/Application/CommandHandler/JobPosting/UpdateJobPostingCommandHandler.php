<?php

declare(strict_types=1);

namespace App\Recruitment\Application\CommandHandler\JobPosting;

use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use App\Recruitment\Application\Command\JobPosting\UpdateJobPostingCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateJobPostingCommandHandler
{
    public function __construct(
        private readonly JobPostingRepositoryInterface $jobPostings,
    ) {
    }

    public function __invoke(UpdateJobPostingCommand $command): void
    {
        $jobPosting = $this->jobPostings->findById(new Uuid($command->jobPostingId));

        if (null === $jobPosting) {
            throw JobPostingNotFoundException::withId($command->jobPostingId);
        }

        $jobPosting->update(new JobTitle($command->title), new JobDescription($command->description));

        $this->jobPostings->save($jobPosting);
    }
}
