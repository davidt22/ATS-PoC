<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Rest\Controller;

use App\Recruitment\Application\Query\GetApplicationDetailQuery;
use App\Recruitment\Domain\Exception\ApplicationNotFoundException;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApplicationDetailController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/api/applications/{id}', name: 'api_applications_detail', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $application = $this->queryBus->ask(new GetApplicationDetailQuery($id));
        } catch (ApplicationNotFoundException|\InvalidArgumentException) {
            return $this->json(['error' => 'Application not found.'], 404);
        }

        return $this->json($application);
    }
}
