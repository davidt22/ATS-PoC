<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\ValueObject;

final class AiScore
{
    public const MIN = 0;
    public const MAX = 100;

    private int $value;

    public function __construct(int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \InvalidArgumentException(sprintf('AI score must be between %d and %d, got %d.', self::MIN, self::MAX, $value));
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }
}
