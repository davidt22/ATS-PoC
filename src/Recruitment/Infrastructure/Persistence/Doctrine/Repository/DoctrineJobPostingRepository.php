<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Repository;

use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineJobPostingRepository implements JobPostingRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(JobPosting $jobPosting): void
    {
        $this->entityManager->persist($jobPosting);
        $this->entityManager->flush();
    }

    public function delete(JobPosting $jobPosting): void
    {
        $this->entityManager->remove($jobPosting);
        $this->entityManager->flush();
    }

    public function findAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('j')
            ->from(JobPosting::class, 'j')
            ->orderBy('j.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(Uuid $id): ?JobPosting
    {
        return $this->entityManager->find(JobPosting::class, $id->value());
    }
}
