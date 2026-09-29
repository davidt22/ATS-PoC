<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidPhoneException;

final class Phone
{
    private const PATTERN = '/^\+?[0-9 ()\-]{6,20}$/';

    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if (!preg_match(self::PATTERN, $trimmed)) {
            throw InvalidPhoneException::forValue($value);
        }

        $this->value = $trimmed;
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
