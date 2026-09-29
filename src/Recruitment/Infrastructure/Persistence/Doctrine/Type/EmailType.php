<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Type;

use App\Recruitment\Domain\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class EmailType extends StringType
{
    public const NAME = 'recruitment_email';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Email
    {
        return null === $value ? null : new Email($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof Email ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
