<?php

declare(strict_types=1);

namespace App\Recruitment\Infrastructure\Ai;

use App\Recruitment\Domain\Service\AiEnrichmentPort;
use App\Recruitment\Domain\Service\AiEnrichmentResult;

/**
 * Deterministic mock of an LLM-based enrichment service. Per the exercise
 * constraints, no real LLM API is called: the summary is a naive excerpt of
 * the CV and the relevance score is a keyword-overlap heuristic between the
 * job posting and the CV text. Swap this adapter for a real one behind
 * AiEnrichmentPort without touching Domain or Application.
 */
final class MockAiEnrichmentAdapter implements AiEnrichmentPort
{
    private const SUMMARY_LENGTH = 280;
    private const STOPWORD_MAX_LENGTH = 3;

    public function enrich(string $jobTitle, string $jobDescription, string $cvText): AiEnrichmentResult
    {
        return new AiEnrichmentResult(
            $this->summarize($cvText),
            $this->score($jobTitle.' '.$jobDescription, $cvText),
        );
    }

    private function summarize(string $cvText): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $cvText) ?? $cvText);

        if (mb_strlen($normalized) <= self::SUMMARY_LENGTH) {
            return $normalized;
        }

        return mb_substr($normalized, 0, self::SUMMARY_LENGTH).'…';
    }

    private function score(string $jobText, string $cvText): int
    {
        $jobWords = $this->significantWords($jobText);
        $cvWords = $this->significantWords($cvText);

        if ([] === $jobWords) {
            return 0;
        }

        $overlap = count(array_intersect($jobWords, $cvWords));

        return (int) round(min(1.0, $overlap / count($jobWords)) * 100);
    }

    /**
     * @return array<string>
     */
    private function significantWords(string $text): array
    {
        preg_match_all('/\p{L}+/u', mb_strtolower($text), $matches);

        $words = array_filter(
            $matches[0],
            static fn (string $word): bool => mb_strlen($word) > self::STOPWORD_MAX_LENGTH,
        );

        return array_values(array_unique($words));
    }
}
