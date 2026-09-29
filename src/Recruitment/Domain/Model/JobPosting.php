<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Model;

use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;

final class JobPosting
{
    private function __construct(
        private readonly Uuid $id,
        private JobTitle $title,
        private JobDescription $description,
    ) {
    }

    public static function create(Uuid $id, JobTitle $title, JobDescription $description): self
    {
        return new self($id, $title, $description);
    }

    public function update(JobTitle $title, JobDescription $description): void
    {
        $this->title = $title;
        $this->description = $description;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function title(): JobTitle
    {
        return $this->title;
    }

    public function description(): JobDescription
    {
        return $this->description;
    }
}
