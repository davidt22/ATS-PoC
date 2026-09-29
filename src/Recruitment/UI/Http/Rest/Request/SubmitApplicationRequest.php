<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Rest\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class SubmitApplicationRequest
{
    #[Assert\NotBlank(message: 'Selecciona un puesto.')]
    public ?string $jobId = null;

    #[Assert\NotBlank(message: 'El nombre completo es obligatorio.')]
    #[Assert\Length(min: 2, max: 150)]
    public ?string $fullName = null;

    #[Assert\NotBlank(message: 'El email es obligatorio.')]
    #[Assert\Email(message: 'El email no es válido.')]
    public ?string $email = null;

    public ?string $phone = null;

    public ?string $notes = null;

    #[Assert\NotBlank(message: 'Pega el texto del CV.')]
    #[Assert\Length(
        min: 50,
        max: 20000,
        minMessage: 'El CV debe tener al menos {{ limit }} caracteres.',
        maxMessage: 'El CV no puede superar los {{ limit }} caracteres.',
    )]
    public ?string $cvText = null;
}
