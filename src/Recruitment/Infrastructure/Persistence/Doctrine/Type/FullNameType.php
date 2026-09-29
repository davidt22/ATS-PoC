<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Type;

use App\Recruitment\Domain\ValueObject\FullName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class FullNameType extends StringType
{
    public const NAME = 'recruitment_full_name';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?FullName
    {
        return null === $value ? null : new FullName($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof FullName ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
