<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class Uuid
{
    private string $value;

    public function __construct(string $value)
    {
        if (!SymfonyUuid::isValid($value)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid UUID.', $value));
        }

        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(SymfonyUuid::v4()->toRfc4122());
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
