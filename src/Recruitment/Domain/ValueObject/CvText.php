<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidCvTextException;

final class CvText
{
    public const MIN_LENGTH = 50;
    public const MAX_LENGTH = 20000;

    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        $length = mb_strlen($trimmed);

        if ($length < self::MIN_LENGTH) {
            throw InvalidCvTextException::tooShort(self::MIN_LENGTH);
        }

        if ($length > self::MAX_LENGTH) {
            throw InvalidCvTextException::tooLong(self::MAX_LENGTH);
        }

        $this->value = $trimmed;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function excerpt(int $length = 280): string
    {
        if (mb_strlen($this->value) <= $length) {
            return $this->value;
        }

        return mb_substr($this->value, 0, $length).'…';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
