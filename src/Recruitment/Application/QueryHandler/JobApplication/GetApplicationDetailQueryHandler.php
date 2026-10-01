<?php

declare(strict_types=1);

namespace App\Recruitment\Application\QueryHandler\JobApplication;

use App\Recruitment\Application\DTO\ApplicationDetailDTO;
use App\Recruitment\Domain\Exception\ApplicationNotFoundException;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Recruitment\Application\Query\JobApplication\GetApplicationDetailQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetApplicationDetailQueryHandler
{
    public function __construct(
        private readonly JobApplicationRepositoryInterface $applicationRepository,
    ) {
    }

    public function __invoke(GetApplicationDetailQuery $query): ApplicationDetailDTO
    {
        $application = $this->applicationRepository->findById(new Uuid($query->applicationId));

        if (null === $application) {
            throw ApplicationNotFoundException::withId($query->applicationId);
        }

        return ApplicationDetailDTO::fromDomain($application);
    }
}
