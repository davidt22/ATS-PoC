<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidJobDescriptionException;
use App\Recruitment\Domain\ValueObject\JobDescription;
use PHPUnit\Framework\TestCase;

final class JobDescriptionTest extends TestCase
{
    public function test_it_accepts_a_valid_description(): void
    {
        self::assertSame('Great job', (new JobDescription(' Great job '))->value());
    }

    public function test_it_rejects_an_empty_description(): void
    {
        $this->expectException(InvalidJobDescriptionException::class);

        new JobDescription('   ');
    }

    public function test_it_rejects_a_description_that_is_too_long(): void
    {
        $this->expectException(InvalidJobDescriptionException::class);

        new JobDescription(str_repeat('a', JobDescription::MAX_LENGTH + 1));
    }
}
