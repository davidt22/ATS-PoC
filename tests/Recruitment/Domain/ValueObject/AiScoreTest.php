<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\ValueObject\AiScore;
use PHPUnit\Framework\TestCase;

final class AiScoreTest extends TestCase
{
    public function test_it_accepts_boundary_values(): void
    {
        self::assertSame(0, (new AiScore(0))->value());
        self::assertSame(100, (new AiScore(100))->value());
    }

    public function test_it_rejects_a_negative_score(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AiScore(-1);
    }

    public function test_it_rejects_a_score_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AiScore(101);
    }
}
