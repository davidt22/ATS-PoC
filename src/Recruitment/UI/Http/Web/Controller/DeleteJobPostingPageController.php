<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Web\Controller;

use App\Recruitment\Application\Command\DeleteJobPostingCommand;
use App\Recruitment\Domain\Exception\JobPostingHasApplicationsException;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Shared\Application\Bus\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteJobPostingPageController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    #[Route('/jobs/{id}/delete', name: 'jobs_delete_page', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function __invoke(Request $request, string $id): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('submit', $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        try {
            $this->commandBus->dispatch(new DeleteJobPostingCommand($id));
            $this->addFlash('success', 'Oferta eliminada correctamente.');
        } catch (JobPostingHasApplicationsException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (JobPostingNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return new RedirectResponse($this->generateUrl('jobs_list_page'));
    }
}
