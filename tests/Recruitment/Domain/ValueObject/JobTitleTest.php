<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidJobTitleException;
use App\Recruitment\Domain\ValueObject\JobTitle;
use PHPUnit\Framework\TestCase;

final class JobTitleTest extends TestCase
{
    public function test_it_accepts_a_valid_title(): void
    {
        self::assertSame('Backend Engineer', (new JobTitle(' Backend Engineer '))->value());
    }

    public function test_it_rejects_an_empty_title(): void
    {
        $this->expectException(InvalidJobTitleException::class);

        new JobTitle('  ');
    }

    public function test_it_rejects_a_title_that_is_too_long(): void
    {
        $this->expectException(InvalidJobTitleException::class);

        new JobTitle(str_repeat('a', 151));
    }
}
