<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Type;

use App\Recruitment\Domain\ValueObject\JobDescription;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

final class JobDescriptionType extends TextType
{
    public const NAME = 'recruitment_job_description';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?JobDescription
    {
        return null === $value ? null : new JobDescription($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof JobDescription ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
