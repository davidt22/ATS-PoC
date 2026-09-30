<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Rest\Controller\JobPosting;

use App\Recruitment\Application\Query\JobPosting\ListJobPostingsQuery;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListJobPostingsController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/api/jobs', name: 'api_jobs_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->queryBus->ask(new ListJobPostingsQuery()));
    }
}
