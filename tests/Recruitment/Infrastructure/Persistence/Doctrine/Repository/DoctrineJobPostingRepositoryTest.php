<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Infrastructure\Persistence\Doctrine\Repository;

use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineJobPostingRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private JobPostingRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(JobPostingRepositoryInterface::class);
        // Functional (WebTestCase) tests commit real rows outside any transaction
        // this class can see; clear them before isolating the rest behind one.
        $this->entityManager->getConnection()->executeStatement('DELETE FROM job_postings');
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }

    public function test_it_saves_and_finds_a_job_posting_by_id(): void
    {
        $id = Uuid::generate();
        $this->repository->save(JobPosting::create($id, new JobTitle('Backend Engineer'), new JobDescription('Great job')));
        $this->entityManager->clear();

        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame('Backend Engineer', $found->title()->value());
    }

    public function test_find_by_id_returns_null_for_unknown_id(): void
    {
        self::assertNull($this->repository->findById(Uuid::generate()));
    }

    public function test_find_all_returns_saved_job_postings(): void
    {
        $this->repository->save(JobPosting::create(Uuid::generate(), new JobTitle('Backend Engineer'), new JobDescription('Great job')));
        $this->repository->save(JobPosting::create(Uuid::generate(), new JobTitle('Frontend Engineer'), new JobDescription('Also great')));
        $this->entityManager->clear();

        $titles = array_map(static fn (JobPosting $j) => $j->title()->value(), $this->repository->findAll());

        self::assertContains('Backend Engineer', $titles);
        self::assertContains('Frontend Engineer', $titles);
    }

    public function test_delete_removes_the_job_posting(): void
    {
        $id = Uuid::generate();
        $jobPosting = JobPosting::create($id, new JobTitle('Backend Engineer'), new JobDescription('Great job'));
        $this->repository->save($jobPosting);

        $this->repository->delete($jobPosting);
        $this->entityManager->clear();

        self::assertNull($this->repository->findById($id));
    }
}
