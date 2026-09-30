<?php

declare(strict_types=1);

namespace App\Recruitment\Application\QueryHandler\JobPosting;

use App\Recruitment\Application\DTO\JobPostingDTO;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Application\Query\JobPosting\ListJobPostingsQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListJobPostingsQueryHandler
{
    public function __construct(
        private readonly JobPostingRepositoryInterface $jobPostings,
    ) {
    }

    /**
     * @return list<JobPostingDTO>
     */
    public function __invoke(ListJobPostingsQuery $query): array
    {
        return array_map(
            static fn ($jobPosting) => JobPostingDTO::fromDomain($jobPosting),
            $this->jobPostings->findAll(),
        );
    }
}
