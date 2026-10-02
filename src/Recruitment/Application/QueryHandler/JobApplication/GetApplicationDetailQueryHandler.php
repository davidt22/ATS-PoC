<?php

declare(strict_types=1);

namespace App\Recruitment\Application\QueryHandler\JobApplication;

use App\Recruitment\Application\DTO\ApplicationDetailDTO;
use App\Recruitment\Application\Query\JobApplication\GetApplicationDetailQuery;
use App\Recruitment\Domain\Exception\ApplicationNotFoundException;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetApplicationDetailQueryHandler
{
    public function __construct(
        private readonly JobApplicationRepositoryInterface $applicationRepository,
        private readonly JobPostingRepositoryInterface $jobPostingRepository,
    ) {
    }

    public function __invoke(GetApplicationDetailQuery $query): ApplicationDetailDTO
    {
        $application = $this->applicationRepository->findById(new Uuid($query->applicationId));

        if (null === $application) {
            throw ApplicationNotFoundException::withId($query->applicationId);
        }

        $jobPosting = $this->jobPostingRepository->findById($application->jobId());

        return ApplicationDetailDTO::fromDomain($application, $jobPosting?->title()->value() ?? '');
    }
}
