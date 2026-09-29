<?php

declare(strict_types=1);

namespace App\Tests\Recruitment;

use App\Recruitment\Application\EventHandler\EnrichApplicationOnApplicationSubmitted;
use App\Recruitment\Domain\Event\ApplicationSubmitted;
use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\ValueObject\ApplicationStatus;
use App\Recruitment\Domain\ValueObject\CvText;
use App\Recruitment\Domain\ValueObject\Email;
use App\Recruitment\Domain\ValueObject\FullName;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Exercises the real, container-wired async enrichment path (the same code
 * `messenger:consume async` runs) against the test database, without
 * depending on Messenger transport mechanics — those belong to the
 * framework, not to this application.
 */
final class EnrichmentAsyncFlowTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }

    public function test_consuming_application_submitted_enriches_the_application(): void
    {
        $container = self::getContainer();
        /** @var JobPostingRepositoryInterface $jobPostings */
        $jobPostings = $container->get(JobPostingRepositoryInterface::class);
        /** @var JobApplicationRepositoryInterface $applications */
        $applications = $container->get(JobApplicationRepositoryInterface::class);

        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('PHP and Symfony experience required')));

        $applicationId = Uuid::generate();
        $application = JobApplication::create(
            $applicationId,
            $jobId,
            new FullName('Ana García'),
            new Email('ana@example.com'),
            null,
            null,
            new CvText('Desarrolladora backend con experiencia en PHP y Symfony construyendo APIs.'),
            new \DateTimeImmutable(),
        );
        $submitted = $application->pullDomainEvents()[0];
        $applications->save($application);

        self::assertInstanceOf(ApplicationSubmitted::class, $submitted);

        $handler = $container->get(EnrichApplicationOnApplicationSubmitted::class);
        $handler($submitted);

        $this->entityManager->clear();
        $enriched = $applications->findById($applicationId);

        self::assertSame(ApplicationStatus::Enriched, $enriched->status());
        self::assertNotNull($enriched->aiSummary());
        self::assertNotNull($enriched->aiScore());
        self::assertGreaterThan(0, $enriched->aiScore()->value());
    }
}
