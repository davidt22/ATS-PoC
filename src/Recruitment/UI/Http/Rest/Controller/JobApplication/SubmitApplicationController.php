<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Rest\Controller\JobApplication;

use App\Recruitment\Application\Command\JobApplication\SubmitApplicationCommand;
use App\Recruitment\UI\Http\Rest\Request\SubmitApplicationRequest;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SubmitApplicationController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly CommandBus $commandBus,
    ) {
    }

    #[Route('/api/applications', name: 'api_applications_submit', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), SubmitApplicationRequest::class, 'json');

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['errors' => $errors], 422);
        }

        $applicationId = Uuid::generate()->value();

        // Guaranteed non-null: $violations is empty, and all four fields carry a NotBlank constraint.
        assert(null !== $dto->jobId && null !== $dto->fullName && null !== $dto->email && null !== $dto->cvText);

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
            return $this->json(['errors' => ['_domain' => $e->getMessage()]], 422);
        }

        return $this->json(['id' => $applicationId], 201);
    }
}
