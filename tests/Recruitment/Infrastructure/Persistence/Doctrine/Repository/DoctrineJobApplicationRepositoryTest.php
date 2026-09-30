<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Infrastructure\Persistence\Doctrine\Repository;

use App\Recruitment\Domain\Exception\DuplicateJobApplicationException;
use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\Repository\ApplicationSearchCriteria;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\ValueObject\ApplicationStatus;
use App\Recruitment\Domain\ValueObject\CvText;
use App\Recruitment\Domain\ValueObject\Email;
use App\Recruitment\Domain\ValueObject\FullName;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineJobApplicationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private JobApplicationRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(JobApplicationRepositoryInterface::class);
        // Functional (WebTestCase) tests commit real rows outside any transaction
        // this class can see; clear them before isolating the rest behind one.
        $this->entityManager->getConnection()->executeStatement('DELETE FROM job_applications');
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }

    public function test_it_saves_and_finds_an_application_by_id(): void
    {
        $id = Uuid::generate();
        $this->repository->save($this->buildApplication($id, Uuid::generate(), 'Ana García', 'ana@example.com'));
        $this->entityManager->clear();

        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame('Ana García', $found->fullName()->value());
        self::assertSame(ApplicationStatus::Received, $found->status());
    }

    public function test_exists_by_job_id_reflects_saved_applications(): void
    {
        $jobId = Uuid::generate();
        self::assertFalse($this->repository->existsByJobId($jobId));

        $this->repository->save($this->buildApplication(Uuid::generate(), $jobId, 'Ana García', 'ana@example.com'));

        self::assertTrue($this->repository->existsByJobId($jobId));
    }

    public function test_exists_by_email_and_job_id_reflects_saved_applications(): void
    {
        $jobId = Uuid::generate();
        $email = new Email('ana@example.com');
        self::assertFalse($this->repository->existsByEmailAndJobId($email, $jobId));

        $this->repository->save($this->buildApplication(Uuid::generate(), $jobId, 'Ana García', 'ana@example.com'));

        self::assertTrue($this->repository->existsByEmailAndJobId($email, $jobId));
        self::assertFalse($this->repository->existsByEmailAndJobId(new Email('otra@example.com'), $jobId));
        self::assertFalse($this->repository->existsByEmailAndJobId($email, Uuid::generate()));
    }

    public function test_the_database_rejects_a_duplicate_email_and_job_id_pair(): void
    {
        $jobId = Uuid::generate();
        $this->repository->save($this->buildApplication(Uuid::generate(), $jobId, 'Ana García', 'ana@example.com'));
        $this->entityManager->clear();

        $this->expectException(DuplicateJobApplicationException::class);

        $this->repository->save($this->buildApplication(Uuid::generate(), $jobId, 'Ana Duplicada', 'ana@example.com'));
    }

    public function test_search_filters_by_status(): void
    {
        $jobId = Uuid::generate();
        $received = $this->buildApplication(Uuid::generate(), $jobId, 'Ana García', 'ana@example.com');
        $enriched = $this->buildApplication(Uuid::generate(), $jobId, 'Luis Pérez', 'luis@example.com');
        $enriched->enrich('Resumen', new \App\Recruitment\Domain\ValueObject\AiScore(80), new \DateTimeImmutable());

        $this->repository->save($received);
        $this->repository->save($enriched);
        $this->entityManager->clear();

        $results = $this->repository->search(new ApplicationSearchCriteria(status: ApplicationStatus::Enriched));

        self::assertCount(1, $results);
        self::assertSame('Luis Pérez', $results[0]->fullName()->value());
    }

    public function test_search_filters_by_job_id(): void
    {
        $jobIdA = Uuid::generate();
        $jobIdB = Uuid::generate();
        $this->repository->save($this->buildApplication(Uuid::generate(), $jobIdA, 'Ana García', 'ana@example.com'));
        $this->repository->save($this->buildApplication(Uuid::generate(), $jobIdB, 'Luis Pérez', 'luis@example.com'));
        $this->entityManager->clear();

        $results = $this->repository->search(new ApplicationSearchCriteria(jobId: $jobIdA->value()));

        self::assertCount(1, $results);
        self::assertSame('Ana García', $results[0]->fullName()->value());
    }

    public function test_search_filters_by_case_insensitive_name_or_email(): void
    {
        $jobId = Uuid::generate();
        $this->repository->save($this->buildApplication(Uuid::generate(), $jobId, 'Ana García', 'ana@example.com'));
        $this->repository->save($this->buildApplication(Uuid::generate(), $jobId, 'Luis Pérez', 'luis@example.com'));
        $this->entityManager->clear();

        $results = $this->repository->search(new ApplicationSearchCriteria(search: 'GARCÍA'));

        self::assertCount(1, $results);
        self::assertSame('ana@example.com', $results[0]->email()->value());
    }

    public function test_search_orders_by_applied_at_descending(): void
    {
        $jobId = Uuid::generate();
        $older = $this->buildApplication(Uuid::generate(), $jobId, 'Ana García', 'ana@example.com', new \DateTimeImmutable('2026-01-01'));
        $newer = $this->buildApplication(Uuid::generate(), $jobId, 'Luis Pérez', 'luis@example.com', new \DateTimeImmutable('2026-02-01'));

        $this->repository->save($older);
        $this->repository->save($newer);
        $this->entityManager->clear();

        $results = $this->repository->search(new ApplicationSearchCriteria(jobId: $jobId->value()));

        self::assertSame('Luis Pérez', $results[0]->fullName()->value());
        self::assertSame('Ana García', $results[1]->fullName()->value());
    }

    private function buildApplication(Uuid $id, Uuid $jobId, string $fullName, string $email, ?\DateTimeImmutable $appliedAt = null): JobApplication
    {
        return JobApplication::create(
            $id,
            $jobId,
            new FullName($fullName),
            new Email($email),
            null,
            null,
            new CvText(str_repeat('a', 60)),
            $appliedAt ?? new \DateTimeImmutable(),
        );
    }
}
