<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Web\Controller;

use App\Recruitment\Application\Command\UpdateJobPostingCommand;
use App\Recruitment\Application\Query\GetJobPostingDetailQuery;
use App\Recruitment\Domain\Exception\JobPostingNotFoundException;
use App\Recruitment\UI\Http\Rest\Request\JobPostingRequest;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EditJobPostingPageController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly ValidatorInterface $validator,
        private readonly CommandBus $commandBus,
    ) {
    }

    #[Route('/jobs/{id}/edit', name: 'jobs_edit_page', methods: ['GET', 'POST'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function __invoke(Request $request, string $id): Response
    {
        try {
            $jobPosting = $this->queryBus->ask(new GetJobPostingDetailQuery($id));
        } catch (JobPostingNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        if (!$request->isMethod('POST')) {
            return $this->render('recruitment/job_form.html.twig', [
                'mode' => 'edit',
                'jobPosting' => $jobPosting,
            ]);
        }

        if (!$this->isCsrfTokenValid('submit', $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $dto = new JobPostingRequest();
        $dto->title = $request->request->get('title');
        $dto->description = $request->request->get('description');

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->render('recruitment/job_form.html.twig', [
                'mode' => 'edit',
                'jobPosting' => $jobPosting,
                'errors' => $errors,
                'formData' => $dto,
            ]);
        }

        try {
            $this->commandBus->dispatch(new UpdateJobPostingCommand($id, $dto->title, $dto->description));
        } catch (\DomainException $e) {
            return $this->render('recruitment/job_form.html.twig', [
                'mode' => 'edit',
                'jobPosting' => $jobPosting,
                'errors' => ['_domain' => $e->getMessage()],
                'formData' => $dto,
            ]);
        }

        $this->addFlash('success', 'Oferta actualizada correctamente.');

        return new RedirectResponse($this->generateUrl('jobs_list_page'));
    }
}
