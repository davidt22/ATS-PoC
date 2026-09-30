<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Web\Controller\JobPosting;

use App\Recruitment\Application\Query\JobPosting\ListJobPostingsQuery;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JobPostingListPageController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/', name: 'jobs_list_page', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('recruitment/jobs_list.html.twig', [
            'jobPostings' => $this->queryBus->ask(new ListJobPostingsQuery()),
        ]);
    }
}
