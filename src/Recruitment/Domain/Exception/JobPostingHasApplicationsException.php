<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Exception;

final class JobPostingHasApplicationsException extends \DomainException
{
    public static function forId(string $id): self
    {
        return new self(sprintf('Job posting "%s" cannot be deleted because it has applications.', $id));
    }
}
