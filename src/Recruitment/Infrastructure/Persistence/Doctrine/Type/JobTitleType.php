<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Type;

use App\Recruitment\Domain\ValueObject\JobTitle;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class JobTitleType extends StringType
{
    public const NAME = 'recruitment_job_title';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?JobTitle
    {
        return null === $value ? null : new JobTitle($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof JobTitle ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
