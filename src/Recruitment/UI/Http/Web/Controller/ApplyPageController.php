<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Web\Controller;

use App\Recruitment\Application\Command\SubmitApplicationCommand;
use App\Recruitment\Application\Query\ListJobPostingsQuery;
use App\Recruitment\UI\Http\Rest\Request\SubmitApplicationRequest;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ApplyPageController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly ValidatorInterface $validator,
        private readonly CommandBus $commandBus,
    ) {
    }

    #[Route('/apply', name: 'apply_page', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $jobPostings = $this->queryBus->ask(new ListJobPostingsQuery());

        if (!$request->isMethod('POST')) {
            return $this->render('recruitment/apply.html.twig', [
                'jobPostings' => $jobPostings,
            ]);
        }

        if (!$this->isCsrfTokenValid('submit', $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $dto = new SubmitApplicationRequest();
        $dto->jobId = $request->request->get('jobId');
        $dto->fullName = $request->request->get('fullName');
        $dto->email = $request->request->get('email');
        $dto->phone = $request->request->get('phone');
        $dto->notes = $request->request->get('notes');
        $dto->cvText = $request->request->get('cvText');

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->render('recruitment/apply.html.twig', [
                'jobPostings' => $jobPostings,
                'errors' => $errors,
                'formData' => $dto,
            ]);
        }

        $applicationId = Uuid::generate()->value();

        try {
            $this->commandBus->dispatch(new SubmitApplicationCommand(
                $applicationId,
                $dto->jobId,
                $dto->fullName,
                $dto->email,
                $dto->phone,
                $dto->notes,
                $dto->cvText,
            ));
        } catch (\DomainException $e) {
            return $this->render('recruitment/apply.html.twig', [
                'jobPostings' => $jobPostings,
                'errors' => ['_domain' => $e->getMessage()],
                'formData' => $dto,
            ]);
        }

        $this->addFlash('success', 'Candidatura enviada correctamente. El enriquecimiento con IA se procesará en segundo plano.');

        return new RedirectResponse($this->generateUrl('application_detail_page', ['id' => $applicationId]));
    }
}
