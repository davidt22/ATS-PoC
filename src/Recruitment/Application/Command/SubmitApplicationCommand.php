<?php

declare(strict_types=1);

namespace App\Recruitment\Application\Command;

use App\Shared\Application\Bus\Command;

final class SubmitApplicationCommand implements Command
{
    public function __construct(
        public readonly string $applicationId,
        public readonly string $jobId,
        public readonly string $fullName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $notes,
        public readonly string $cvText,
    ) {
    }
}
