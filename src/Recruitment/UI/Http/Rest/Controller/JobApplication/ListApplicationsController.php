<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Rest\Controller\JobApplication;

use App\Recruitment\Application\Query\JobApplication\ListApplicationsQuery;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ListApplicationsController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/api/applications', name: 'api_applications_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $applications = $this->queryBus->ask(new ListApplicationsQuery(
            $request->query->get('status'),
            $request->query->get('jobId'),
            $request->query->get('search'),
        ));

        return $this->json($applications);
    }
}
