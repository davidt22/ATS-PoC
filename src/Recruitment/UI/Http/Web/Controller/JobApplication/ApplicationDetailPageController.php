<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Web\Controller\JobApplication;

use App\Recruitment\Application\Query\JobApplication\GetApplicationDetailQuery;
use App\Recruitment\Domain\Exception\ApplicationNotFoundException;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ApplicationDetailPageController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/applications/{id}', name: 'application_detail_page', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function __invoke(string $id): Response
    {
        try {
            $application = $this->queryBus->ask(new GetApplicationDetailQuery($id));
        } catch (ApplicationNotFoundException|\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Application not found.', $e);
        }

        return $this->render('recruitment/application_detail.html.twig', [
            'application' => $application,
        ]);
    }
}
