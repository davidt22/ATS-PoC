<?php

declare(strict_types=1);

namespace App\Recruitment\Application\CommandHandler;

use App\Recruitment\Application\Command\CreateJobPostingCommand;
use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateJobPostingCommandHandler
{
    public function __construct(
        private readonly JobPostingRepositoryInterface $jobPostings,
    ) {
    }

    public function __invoke(CreateJobPostingCommand $command): void
    {
        $jobPosting = JobPosting::create(
            new Uuid($command->jobPostingId),
            new JobTitle($command->title),
            new JobDescription($command->description),
        );

        $this->jobPostings->save($jobPosting);
    }
}
