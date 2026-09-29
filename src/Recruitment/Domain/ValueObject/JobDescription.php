<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidJobDescriptionException;

final class JobDescription
{
    public const MAX_LENGTH = 5000;

    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw InvalidJobDescriptionException::forEmptyValue();
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw InvalidJobDescriptionException::tooLong(self::MAX_LENGTH);
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
