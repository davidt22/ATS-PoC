<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Infrastructure\Ai;

use App\Recruitment\Infrastructure\Ai\MockAiEnrichmentAdapter;
use PHPUnit\Framework\TestCase;

final class MockAiEnrichmentAdapterTest extends TestCase
{
    public function test_it_is_deterministic_for_the_same_inputs(): void
    {
        $adapter = new MockAiEnrichmentAdapter();

        $first = $adapter->enrich('Backend Engineer', 'PHP and Symfony experience required', 'I have 5 years of PHP and Symfony experience.');
        $second = $adapter->enrich('Backend Engineer', 'PHP and Symfony experience required', 'I have 5 years of PHP and Symfony experience.');

        self::assertSame($first->summary, $second->summary);
        self::assertSame($first->score, $second->score);
    }

    public function test_score_is_higher_when_cv_shares_more_keywords_with_the_job(): void
    {
        $adapter = new MockAiEnrichmentAdapter();

        $relevant = $adapter->enrich('Backend Engineer', 'We need PHP Symfony Doctrine experience', 'Senior PHP Symfony Doctrine developer with hexagonal architecture background.');
        $irrelevant = $adapter->enrich('Backend Engineer', 'We need PHP Symfony Doctrine experience', 'Professional oil painter and landscape gardener.');

        self::assertGreaterThan($irrelevant->score, $relevant->score);
    }

    public function test_score_is_within_bounds(): void
    {
        $adapter = new MockAiEnrichmentAdapter();

        $result = $adapter->enrich('Backend Engineer', 'PHP Symfony', str_repeat('lorem ipsum dolor sit amet ', 50));

        self::assertGreaterThanOrEqual(0, $result->score);
        self::assertLessThanOrEqual(100, $result->score);
    }

    public function test_summary_truncates_long_cv_text(): void
    {
        $adapter = new MockAiEnrichmentAdapter();
        $longCv = str_repeat('a', 500);

        $result = $adapter->enrich('Backend Engineer', 'PHP', $longCv);

        self::assertLessThan(500, mb_strlen($result->summary));
        self::assertStringEndsWith('…', $result->summary);
    }
}
