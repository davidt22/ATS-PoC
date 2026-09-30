<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Repository;

use App\Recruitment\Domain\Exception\DuplicateJobApplicationException;
use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\Repository\ApplicationSearchCriteria;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineJobApplicationRepository implements JobApplicationRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(JobApplication $application): void
    {
        try {
            $this->entityManager->persist($application);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw DuplicateJobApplicationException::forEmailAndJobId(
                $application->email()->value(),
                $application->jobId()->value(),
            );
        }
    }

    public function findById(Uuid $id): ?JobApplication
    {
        return $this->entityManager->find(JobApplication::class, $id->value());
    }

    public function existsByJobId(Uuid $jobId): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(JobApplication::class, 'a')
            ->andWhere('a.jobId = :jobId')
            ->setParameter('jobId', $jobId->value())
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function existsByEmailAndJobId(Email $email, Uuid $jobId): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(JobApplication::class, 'a')
            ->andWhere('a.email = :email')
            ->andWhere('a.jobId = :jobId')
            ->setParameter('email', $email->value())
            ->setParameter('jobId', $jobId->value())
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function search(ApplicationSearchCriteria $criteria): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(JobApplication::class, 'a')
            ->orderBy('a.appliedAt', 'DESC');

        if (null !== $criteria->status) {
            $qb->andWhere('a.status = :status')->setParameter('status', $criteria->status->value);
        }

        if (null !== $criteria->jobId) {
            $qb->andWhere('a.jobId = :jobId')->setParameter('jobId', $criteria->jobId);
        }

        if (null !== $criteria->search) {
            $qb->andWhere('LOWER(a.fullName) LIKE :search OR LOWER(a.email) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($criteria->search).'%');
        }

        return $qb->getQuery()->getResult();
    }
}
