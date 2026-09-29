<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidFullNameException;

final class FullName
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 150;

    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        $length = mb_strlen($trimmed);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw InvalidFullNameException::forValue($value);
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
