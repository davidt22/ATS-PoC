<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidFullNameException;
use App\Recruitment\Domain\ValueObject\FullName;
use PHPUnit\Framework\TestCase;

final class FullNameTest extends TestCase
{
    public function test_it_accepts_a_valid_name(): void
    {
        self::assertSame('Ana García', (new FullName(' Ana García '))->value());
    }

    public function test_it_rejects_a_name_that_is_too_short(): void
    {
        $this->expectException(InvalidFullNameException::class);

        new FullName('A');
    }

    public function test_it_rejects_an_empty_name(): void
    {
        $this->expectException(InvalidFullNameException::class);

        new FullName('   ');
    }

    public function test_it_rejects_a_name_that_is_too_long(): void
    {
        $this->expectException(InvalidFullNameException::class);

        new FullName(str_repeat('a', 151));
    }
}
