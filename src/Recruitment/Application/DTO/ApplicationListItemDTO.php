<?php

declare(strict_types=1);

namespace App\Recruitment\Application\DTO;

use App\Recruitment\Domain\Model\JobApplication;

final class ApplicationListItemDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly string $email,
        public readonly string $jobId,
        public readonly string $jobTitle,
        public readonly string $status,
        public readonly ?int $aiScore,
        public readonly string $appliedAt,
    ) {
    }

    public static function fromDomain(JobApplication $application, string $jobTitle): self
    {
        return new self(
            $application->id()->value(),
            $application->fullName()->value(),
            $application->email()->value(),
            $application->jobId()->value(),
            $jobTitle,
            $application->status()->value,
            $application->aiScore()?->value(),
            $application->appliedAt()->format(DATE_ATOM),
        );
    }
}
