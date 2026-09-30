<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Application\CommandHandler;

use App\Recruitment\Domain\Event\ApplicationSubmitted;
use App\Recruitment\Domain\Exception\DuplicateJobApplicationException;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\Repository\ApplicationSearchCriteria;
use App\Recruitment\Domain\ValueObject\ApplicationStatus;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use App\Recruitment\Application\Command\JobApplication\SubmitApplicationCommand;
use App\Recruitment\Application\CommandHandler\JobApplication\SubmitApplicationCommandHandler;
use App\Tests\Recruitment\Application\Fake\FixedClock;
use App\Tests\Recruitment\Application\Fake\InMemoryJobApplicationRepository;
use App\Tests\Recruitment\Application\Fake\InMemoryJobPostingRepository;
use App\Tests\Recruitment\Application\Fake\RecordingEventBus;
use PHPUnit\Framework\TestCase;

final class SubmitApplicationCommandHandlerTest extends TestCase
{
    public function test_it_creates_and_persists_the_application(): void
    {
        $repository = new InMemoryJobApplicationRepository();
        $jobPostings = new InMemoryJobPostingRepository();
        $eventBus = new RecordingEventBus();
        $now = new \DateTimeImmutable('2026-01-01 10:00:00');
        $handler = new SubmitApplicationCommandHandler($repository, $jobPostings, $eventBus, new FixedClock($now));

        $applicationId = Uuid::generate()->value();
        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $handler(new SubmitApplicationCommand(
            $applicationId,
            $jobId->value(),
            'Ana García',
            'ana@example.com',
            '+34600111222',
            'Nota opcional',
            str_repeat('a', 60),
        ));

        $application = $repository->findById(new Uuid($applicationId));

        self::assertNotNull($application);
        self::assertSame(ApplicationStatus::Received, $application->status());
        self::assertSame($now, $application->appliedAt());

        $published = $eventBus->published();
        self::assertCount(1, $published);
        self::assertInstanceOf(ApplicationSubmitted::class, $published[0]);
    }

    public function test_blank_optional_fields_are_stored_as_null(): void
    {
        $repository = new InMemoryJobApplicationRepository();
        $jobPostings = new InMemoryJobPostingRepository();
        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));
        $handler = new SubmitApplicationCommandHandler($repository, $jobPostings, new RecordingEventBus(), new FixedClock(new \DateTimeImmutable()));

        $applicationId = Uuid::generate()->value();

        $handler(new SubmitApplicationCommand(
            $applicationId,
            $jobId->value(),
            'Ana García',
            'ana@example.com',
            '   ',
            '   ',
            str_repeat('a', 60),
        ));

        $application = $repository->findById(new Uuid($applicationId));

        self::assertNull($application->phone());
        self::assertNull($application->notes());
    }

    public function test_it_throws_when_the_job_posting_does_not_exist(): void
    {
        $handler = new SubmitApplicationCommandHandler(
            new InMemoryJobApplicationRepository(),
            new InMemoryJobPostingRepository(),
            new RecordingEventBus(),
            new FixedClock(new \DateTimeImmutable()),
        );

        $this->expectException(JobPostingNotFoundException::class);

        $handler(new SubmitApplicationCommand(
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            'Ana García',
            'ana@example.com',
            null,
            null,
            str_repeat('a', 60),
        ));
    }

    public function test_it_throws_when_an_application_with_the_same_email_already_exists_for_the_job(): void
    {
        $repository = new InMemoryJobApplicationRepository();
        $jobPostings = new InMemoryJobPostingRepository();
        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $handler = new SubmitApplicationCommandHandler($repository, $jobPostings, new RecordingEventBus(), new FixedClock(new \DateTimeImmutable()));

        $handler(new SubmitApplicationCommand(
            Uuid::generate()->value(),
            $jobId->value(),
            'Ana García',
            'ana@example.com',
            null,
            null,
            str_repeat('a', 60),
        ));

        $this->expectException(DuplicateJobApplicationException::class);

        $handler(new SubmitApplicationCommand(
            Uuid::generate()->value(),
            $jobId->value(),
            'Ana Otra',
            'ANA@example.com',
            null,
            null,
            str_repeat('a', 60),
        ));
    }

    public function test_it_allows_the_same_email_to_apply_to_a_different_job_posting(): void
    {
        $repository = new InMemoryJobApplicationRepository();
        $jobPostings = new InMemoryJobPostingRepository();
        $jobIdA = Uuid::generate();
        $jobIdB = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobIdA, new JobTitle('Backend Engineer'), new JobDescription('Great job')));
        $jobPostings->save(JobPosting::create($jobIdB, new JobTitle('Frontend Engineer'), new JobDescription('Another great job')));

        $handler = new SubmitApplicationCommandHandler($repository, $jobPostings, new RecordingEventBus(), new FixedClock(new \DateTimeImmutable()));

        $handler(new SubmitApplicationCommand(
            Uuid::generate()->value(),
            $jobIdA->value(),
            'Ana García',
            'ana@example.com',
            null,
            null,
            str_repeat('a', 60),
        ));

        $handler(new SubmitApplicationCommand(
            Uuid::generate()->value(),
            $jobIdB->value(),
            'Ana García',
            'ana@example.com',
            null,
            null,
            str_repeat('a', 60),
        ));

        self::assertCount(2, $repository->search(new ApplicationSearchCriteria()));
    }
}
