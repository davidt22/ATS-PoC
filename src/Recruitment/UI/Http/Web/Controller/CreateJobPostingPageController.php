<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Web\Controller;

use App\Recruitment\Application\Command\CreateJobPostingCommand;
use App\Recruitment\UI\Http\Rest\Request\JobPostingRequest;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateJobPostingPageController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly CommandBus $commandBus,
    ) {
    }

    #[Route('/jobs/new', name: 'jobs_create_page', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->render('recruitment/job_form.html.twig', [
                'mode' => 'create',
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
                'mode' => 'create',
                'errors' => $errors,
                'formData' => $dto,
            ]);
        }

        $jobPostingId = Uuid::generate()->value();

        try {
            $this->commandBus->dispatch(new CreateJobPostingCommand($jobPostingId, $dto->title, $dto->description));
        } catch (\DomainException $e) {
            return $this->render('recruitment/job_form.html.twig', [
                'mode' => 'create',
                'errors' => ['_domain' => $e->getMessage()],
                'formData' => $dto,
            ]);
        }

        $this->addFlash('success', 'Oferta creada correctamente.');

        return new RedirectResponse($this->generateUrl('jobs_list_page'));
    }
}
