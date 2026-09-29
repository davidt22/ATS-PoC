<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Exception;

final class InvalidPhoneException extends \DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid phone number.', $value));
    }
}
