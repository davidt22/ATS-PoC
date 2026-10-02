<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Application\CommandHandler;

use App\Recruitment\Application\Command\JobPosting\DeleteJobPostingCommand;
use App\Recruitment\Application\CommandHandler\JobPosting\DeleteJobPostingCommandHandler;
use App\Recruitment\Domain\Exception\JobPostingHasApplicationsException;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\ValueObject\CvText;
use App\Recruitment\Domain\ValueObject\Email;
use App\Recruitment\Domain\ValueObject\FullName;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Recruitment\Application\Fake\InMemoryJobApplicationRepository;
use App\Tests\Recruitment\Application\Fake\InMemoryJobPostingRepository;
use PHPUnit\Framework\TestCase;

final class DeleteJobPostingCommandHandlerTest extends TestCase
{
    public function test_it_deletes_a_job_posting_without_applications(): void
    {
        $jobPostings = new InMemoryJobPostingRepository();
        $jobPostingId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobPostingId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $handler = new DeleteJobPostingCommandHandler($jobPostings, new InMemoryJobApplicationRepository());

        $handler(new DeleteJobPostingCommand($jobPostingId->value()));

        self::assertNull($jobPostings->findById($jobPostingId));
    }

    public function test_it_refuses_to_delete_a_job_posting_with_applications(): void
    {
        $jobPostings = new InMemoryJobPostingRepository();
        $jobPostingId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobPostingId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $applications = new InMemoryJobApplicationRepository();
        $application = JobApplication::create(
            Uuid::generate(),
            $jobPostingId,
            new FullName('Ana García'),
            new Email('ana@example.com'),
            null,
            null,
            new CvText(str_repeat('a', 60)),
            new \DateTimeImmutable(),
        );
        $applications->save($application);

        $handler = new DeleteJobPostingCommandHandler($jobPostings, $applications);

        $this->expectException(JobPostingHasApplicationsException::class);

        $handler(new DeleteJobPostingCommand($jobPostingId->value()));
    }

    public function test_it_throws_when_the_job_posting_does_not_exist(): void
    {
        $handler = new DeleteJobPostingCommandHandler(new InMemoryJobPostingRepository(), new InMemoryJobApplicationRepository());

        $this->expectException(JobPostingNotFoundException::class);

        $handler(new DeleteJobPostingCommand(Uuid::generate()->value()));
    }
}
