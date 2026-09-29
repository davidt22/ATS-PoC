<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Persistence\Doctrine\Type;

use App\Recruitment\Domain\ValueObject\AiScore;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class AiScoreType extends Type
{
    public const NAME = 'recruitment_ai_score';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AiScore
    {
        return null === $value ? null : new AiScore((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof AiScore ? $value->value() : (int) $value;
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
