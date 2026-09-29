<?php

declare(strict_types=1);

namespace App\Recruitment\UI\Http\Rest\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class JobPostingRequest
{
    #[Assert\NotBlank(message: 'El título es obligatorio.')]
    #[Assert\Length(min: 2, max: 150)]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'La descripción es obligatoria.')]
    #[Assert\Length(max: 5000)]
    public ?string $description = null;
}
