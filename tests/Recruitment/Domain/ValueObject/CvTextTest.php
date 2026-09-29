<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\ValueObject;

use App\Recruitment\Domain\Exception\InvalidCvTextException;
use App\Recruitment\Domain\ValueObject\CvText;
use PHPUnit\Framework\TestCase;

final class CvTextTest extends TestCase
{
    public function test_it_accepts_a_cv_text_at_the_minimum_length(): void
    {
        $text = str_repeat('a', CvText::MIN_LENGTH);

        self::assertSame($text, (new CvText($text))->value());
    }

    public function test_it_rejects_a_cv_text_that_is_too_short(): void
    {
        $this->expectException(InvalidCvTextException::class);

        new CvText(str_repeat('a', CvText::MIN_LENGTH - 1));
    }

    public function test_it_rejects_a_cv_text_that_is_too_long(): void
    {
        $this->expectException(InvalidCvTextException::class);

        new CvText(str_repeat('a', CvText::MAX_LENGTH + 1));
    }

    public function test_excerpt_truncates_long_text_with_ellipsis(): void
    {
        $cvText = new CvText(str_repeat('a', 400));

        $excerpt = $cvText->excerpt(280);

        self::assertSame(281, mb_strlen($excerpt));
        self::assertStringEndsWith('…', $excerpt);
    }

    public function test_excerpt_returns_full_text_when_shorter_than_limit(): void
    {
        $text = str_repeat('a', CvText::MIN_LENGTH);
        $cvText = new CvText($text);

        self::assertSame($text, $cvText->excerpt(280));
    }
}
