<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Service;

/**
 * Port for the AI capability that summarizes a CV and scores its relevance
 * against a job posting. Implemented by a mocked adapter (see constraints:
 * no real LLM API is called by this exercise).
 */
interface AiEnrichmentPort
{
    public function enrich(string $jobTitle, string $jobDescription, string $cvText): AiEnrichmentResult;
}
