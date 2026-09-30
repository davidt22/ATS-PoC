<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Web\Controller\JobPosting;

use App\Recruitment\Application\Query\JobPosting\GetJobPostingDetailQuery;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class JobPostingDetailPageController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/jobs/{id}', name: 'job_detail_page', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function __invoke(string $id): Response
    {
        try {
            $jobPosting = $this->queryBus->ask(new GetJobPostingDetailQuery($id));
        } catch (JobPostingNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        return $this->render('recruitment/job_detail.html.twig', [
            'jobPosting' => $jobPosting,
        ]);
    }
}
