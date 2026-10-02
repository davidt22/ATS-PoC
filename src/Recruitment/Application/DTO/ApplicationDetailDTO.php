<?php

declare(strict_types=1);

namespace App\Recruitment\Application\DTO;

use App\Recruitment\Domain\Model\JobApplication;

final class ApplicationDetailDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $notes,
        public readonly string $jobId,
        public readonly string $jobTitle,
        public readonly string $cvText,
        public readonly string $status,
        public readonly ?string $aiSummary,
        public readonly ?int $aiScore,
        public readonly string $appliedAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromDomain(JobApplication $application, string $jobTitle): self
    {
        return new self(
            $application->id()->value(),
            $application->fullName()->value(),
            $application->email()->value(),
            $application->phone()?->value(),
            $application->notes(),
            $application->jobId()->value(),
            $jobTitle,
            $application->cvText()->value(),
            $application->status()->value,
            $application->aiSummary(),
            $application->aiScore()?->value(),
            $application->appliedAt()->format(DATE_ATOM),
            $application->updatedAt()->format(DATE_ATOM),
        );
    }
}
