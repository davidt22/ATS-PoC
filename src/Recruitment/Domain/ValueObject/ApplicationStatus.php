<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\ValueObject;

enum ApplicationStatus: string
{
    case Received = 'received';
    case Enriched = 'enriched';
}
