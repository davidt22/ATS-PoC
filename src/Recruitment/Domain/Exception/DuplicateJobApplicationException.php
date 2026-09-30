<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Exception;

final class DuplicateJobApplicationException extends \DomainException
{
    public static function forEmailAndJobId(string $email, string $jobId): self
    {
        return new self(sprintf(
            'An application with email "%s" already exists for job posting "%s".',
            $email,
            $jobId,
        ));
    }
}
