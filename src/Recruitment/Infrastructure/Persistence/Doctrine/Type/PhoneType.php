<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Type;

use App\Recruitment\Domain\ValueObject\Phone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class PhoneType extends StringType
{
    public const NAME = 'recruitment_phone';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Phone
    {
        return null === $value ? null : new Phone($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof Phone ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
