<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidEmailException;
use App\Recruitment\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_it_normalizes_a_valid_email_to_lowercase(): void
    {
        self::assertSame('ana@example.com', (new Email(' Ana@Example.com '))->value());
    }

    public function test_it_rejects_a_malformed_email(): void
    {
        $this->expectException(InvalidEmailException::class);

        new Email('not-an-email');
    }
}
