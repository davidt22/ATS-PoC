<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Type;

use App\Recruitment\Domain\ValueObject\CvText;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

final class CvTextType extends TextType
{
    public const NAME = 'recruitment_cv_text';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?CvText
    {
        return null === $value ? null : new CvText($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof CvText ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
