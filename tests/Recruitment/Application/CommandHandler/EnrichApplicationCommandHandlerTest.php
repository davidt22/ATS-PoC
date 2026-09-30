<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Application\CommandHandler;

use App\Recruitment\Domain\Event\ApplicationEnriched;
use App\Recruitment\Domain\Exception\ApplicationNotFoundException;
use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\ValueObject\ApplicationStatus;
use App\Recruitment\Domain\ValueObject\CvText;
use App\Recruitment\Domain\ValueObject\Email;
use App\Recruitment\Domain\ValueObject\FullName;
use App\Shared\Domain\ValueObject\Uuid;
use App\Recruitment\Application\Command\JobApplication\EnrichApplicationCommand;
use App\Recruitment\Application\CommandHandler\JobApplication\EnrichApplicationCommandHandler;
use App\Tests\Recruitment\Application\Fake\FixedClock;
use App\Tests\Recruitment\Application\Fake\InMemoryJobApplicationRepository;
use App\Tests\Recruitment\Application\Fake\RecordingEventBus;
use PHPUnit\Framework\TestCase;

final class EnrichApplicationCommandHandlerTest extends TestCase
{
    public function test_it_enriches_an_existing_application(): void
    {
        $repository = new InMemoryJobApplicationRepository();
        $applicationId = Uuid::generate();

        $application = JobApplication::create(
            $applicationId,
            Uuid::generate(),
            new FullName('Ana García'),
            new Email('ana@example.com'),
            null,
            null,
            new CvText(str_repeat('a', 60)),
            new \DateTimeImmutable('2026-01-01 10:00:00'),
        );
        $application->pullDomainEvents();
        $repository->save($application);

        $eventBus = new RecordingEventBus();
        $enrichedAt = new \DateTimeImmutable('2026-01-01 10:05:00');
        $handler = new EnrichApplicationCommandHandler($repository, $eventBus, new FixedClock($enrichedAt));

        $handler(new EnrichApplicationCommand($applicationId->value(), 'Resumen', 75));

        $updated = $repository->findById($applicationId);
        self::assertSame(ApplicationStatus::Enriched, $updated->status());
        self::assertSame('Resumen', $updated->aiSummary());
        self::assertSame(75, $updated->aiScore()?->value());

        $published = $eventBus->published();
        self::assertCount(1, $published);
        self::assertInstanceOf(ApplicationEnriched::class, $published[0]);
    }

    public function test_it_throws_when_the_application_does_not_exist(): void
    {
        $handler = new EnrichApplicationCommandHandler(
            new InMemoryJobApplicationRepository(),
            new RecordingEventBus(),
            new FixedClock(new \DateTimeImmutable()),
        );

        $this->expectException(ApplicationNotFoundException::class);

        $handler(new EnrichApplicationCommand(Uuid::generate()->value(), 'Resumen', 50));
    }
}
