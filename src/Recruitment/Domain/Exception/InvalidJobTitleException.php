<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Exception;

final class InvalidJobTitleException extends \DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid job title.', $value));
    }
}
