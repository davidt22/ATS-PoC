<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class UuidType extends StringType
{
    public const NAME = 'recruitment_uuid';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Uuid
    {
        return null === $value ? null : new Uuid($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof Uuid ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
