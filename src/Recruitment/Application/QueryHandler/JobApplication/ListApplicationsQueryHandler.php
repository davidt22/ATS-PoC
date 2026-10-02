<?php

declare(strict_types=1);

namespace App\Recruitment\Application\QueryHandler\JobApplication;

use App\Recruitment\Application\DTO\ApplicationListItemDTO;
use App\Recruitment\Application\Query\JobApplication\ListApplicationsQuery;
use App\Recruitment\Domain\Repository\ApplicationSearchCriteria;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\ValueObject\ApplicationStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListApplicationsQueryHandler
{
    public function __construct(
        private readonly JobApplicationRepositoryInterface $applicationRepository,
        private readonly JobPostingRepositoryInterface $jobPostingRepository,
    ) {
    }

    /**
     * @return list<ApplicationListItemDTO>
     */
    public function __invoke(ListApplicationsQuery $query): array
    {
        $status = null !== $query->status && '' !== $query->status
            ? ApplicationStatus::tryFrom($query->status)
            : null;

        $criteria = new ApplicationSearchCriteria(
            $status,
            null !== $query->jobId && '' !== $query->jobId ? $query->jobId : null,
            null !== $query->search && '' !== $query->search ? $query->search : null,
        );

        $jobTitles = [];
        foreach ($this->jobPostingRepository->findAll() as $jobPosting) {
            $jobTitles[$jobPosting->id()->value()] = $jobPosting->title()->value();
        }

        return array_map(
            static fn ($application) => ApplicationListItemDTO::fromDomain(
                $application,
                $jobTitles[$application->jobId()->value()] ?? '',
            ),
            $this->applicationRepository->search($criteria),
        );
    }
}
