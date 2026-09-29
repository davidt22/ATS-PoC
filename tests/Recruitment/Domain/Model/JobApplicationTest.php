<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\Model;

use App\Recruitment\Domain\Event\ApplicationEnriched;
use App\Recruitment\Domain\Event\ApplicationSubmitted;
use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\ValueObject\AiScore;
use App\Recruitment\Domain\ValueObject\ApplicationStatus;
use App\Recruitment\Domain\ValueObject\CvText;
use App\Recruitment\Domain\ValueObject\Email;
use App\Recruitment\Domain\ValueObject\FullName;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class JobApplicationTest extends TestCase
{
    public function test_create_sets_initial_state_and_records_application_submitted(): void
    {
        $id = Uuid::generate();
        $jobId = Uuid::generate();
        $appliedAt = new \DateTimeImmutable('2026-01-01 10:00:00');

        $application = JobApplication::create(
            $id,
            $jobId,
            new FullName('Ana García'),
            new Email('ana@example.com'),
            null,
            null,
            new CvText(str_repeat('a', 60)),
            $appliedAt,
        );

        self::assertSame(ApplicationStatus::Received, $application->status());
        self::assertSame($appliedAt, $application->appliedAt());
        self::assertSame($appliedAt, $application->updatedAt());
        self::assertNull($application->aiSummary());
        self::assertNull($application->aiScore());

        $events = $application->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ApplicationSubmitted::class, $events[0]);
        self::assertSame($id->value(), $events[0]->applicationId());
        self::assertSame($jobId->value(), $events[0]->jobId());
    }

    public function test_pull_domain_events_empties_the_pending_list(): void
    {
        $application = $this->createApplication();

        $application->pullDomainEvents();

        self::assertSame([], $application->pullDomainEvents());
    }

    public function test_enrich_transitions_status_and_records_application_enriched(): void
    {
        $application = $this->createApplication();
        $application->pullDomainEvents();

        $enrichedAt = new \DateTimeImmutable('2026-01-01 10:05:00');
        $application->enrich('Resumen del CV', new AiScore(87), $enrichedAt);

        self::assertSame(ApplicationStatus::Enriched, $application->status());
        self::assertSame('Resumen del CV', $application->aiSummary());
        self::assertSame(87, $application->aiScore()?->value());
        self::assertSame($enrichedAt, $application->updatedAt());

        $events = $application->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ApplicationEnriched::class, $events[0]);
        self::assertSame(87, $events[0]->score());
    }

    public function test_enrich_is_idempotent_when_already_enriched(): void
    {
        $application = $this->createApplication();
        $application->pullDomainEvents();
        $application->enrich('Resumen original', new AiScore(50), new \DateTimeImmutable('2026-01-01 10:05:00'));
        $application->pullDomainEvents();

        $application->enrich('Resumen duplicado', new AiScore(90), new \DateTimeImmutable('2026-01-01 10:10:00'));

        self::assertSame('Resumen original', $application->aiSummary());
        self::assertSame(50, $application->aiScore()?->value());
        self::assertSame([], $application->pullDomainEvents());
    }

    private function createApplication(): JobApplication
    {
        return JobApplication::create(
            Uuid::generate(),
            Uuid::generate(),
            new FullName('Ana García'),
            new Email('ana@example.com'),
            null,
            null,
            new CvText(str_repeat('a', 60)),
            new \DateTimeImmutable('2026-01-01 10:00:00'),
        );
    }
}
