<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Exception;

final class InvalidJobDescriptionException extends \DomainException
{
    public static function forEmptyValue(): self
    {
        return new self('The job description must not be empty.');
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(sprintf('The job description must not exceed %d characters.', $maxLength));
    }
}
