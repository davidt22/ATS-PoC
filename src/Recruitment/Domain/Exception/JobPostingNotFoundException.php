<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Exception;

final class JobPostingNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Job posting with id "%s" was not found.', $id));
    }
}
