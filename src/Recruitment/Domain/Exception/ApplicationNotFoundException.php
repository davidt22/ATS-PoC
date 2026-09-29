<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Exception;

final class ApplicationNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Application with id "%s" was not found.', $id));
    }
}
