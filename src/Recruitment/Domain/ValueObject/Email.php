<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidEmailException;

final class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmailException::forValue($value);
        }

        $this->value = mb_strtolower($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
