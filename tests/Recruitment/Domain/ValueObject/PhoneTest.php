<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidPhoneException;
use App\Recruitment\Domain\ValueObject\Phone;
use PHPUnit\Framework\TestCase;

final class PhoneTest extends TestCase
{
    public function test_it_accepts_a_valid_international_phone(): void
    {
        self::assertSame('+34 600 111 222', (new Phone('+34 600 111 222'))->value());
    }

    public function test_it_rejects_a_value_with_letters(): void
    {
        $this->expectException(InvalidPhoneException::class);

        new Phone('call me maybe');
    }

    public function test_it_rejects_a_value_that_is_too_short(): void
    {
        $this->expectException(InvalidPhoneException::class);

        new Phone('123');
    }
}
