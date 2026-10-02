<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Application\CommandHandler;

use App\Recruitment\Application\Command\JobPosting\CreateJobPostingCommand;
use App\Recruitment\Application\CommandHandler\JobPosting\CreateJobPostingCommandHandler;
use App\Recruitment\Domain\Exception\InvalidJobTitleException;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Recruitment\Application\Fake\InMemoryJobPostingRepository;
use PHPUnit\Framework\TestCase;

final class CreateJobPostingCommandHandlerTest extends TestCase
{
    public function test_it_creates_and_persists_a_job_posting(): void
    {
        $repository = new InMemoryJobPostingRepository();
        $handler = new CreateJobPostingCommandHandler($repository);
        $jobPostingId = Uuid::generate()->value();

        $handler(new CreateJobPostingCommand($jobPostingId, 'Backend Engineer', 'Great job'));

        $jobPosting = $repository->findById(new Uuid($jobPostingId));
        self::assertNotNull($jobPosting);
        self::assertSame('Backend Engineer', $jobPosting->title()->value());
        self::assertSame('Great job', $jobPosting->description()->value());
    }

    public function test_it_propagates_domain_validation_errors(): void
    {
        $handler = new CreateJobPostingCommandHandler(new InMemoryJobPostingRepository());

        $this->expectException(InvalidJobTitleException::class);

        $handler(new CreateJobPostingCommand(Uuid::generate()->value(), '', 'Great job'));
    }
}
