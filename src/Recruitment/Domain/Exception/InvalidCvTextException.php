<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Exception;

final class InvalidCvTextException extends \DomainException
{
    public static function tooShort(int $minLength): self
    {
        return new self(sprintf('The CV text must be at least %d characters long.', $minLength));
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(sprintf('The CV text must not exceed %d characters.', $maxLength));
    }
}
