<?php

declare(strict_types=1);

namespace App\Recruitment\Application\QueryHandler;

use App\Recruitment\Application\DTO\JobPostingDTO;
use App\Recruitment\Application\Query\GetJobPostingDetailQuery;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetJobPostingDetailQueryHandler
{
    public function __construct(
        private readonly JobPostingRepositoryInterface $jobPostings,
    ) {
    }

    public function __invoke(GetJobPostingDetailQuery $query): JobPostingDTO
    {
        $jobPosting = $this->jobPostings->findById(new Uuid($query->jobPostingId));

        if (null === $jobPosting) {
            throw JobPostingNotFoundException::withId($query->jobPostingId);
        }

        return JobPostingDTO::fromDomain($jobPosting);
    }
}
