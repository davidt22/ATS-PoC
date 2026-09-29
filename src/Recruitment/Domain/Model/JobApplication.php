<?php

declare(strict_types=1);

namespace App\Recruitment\Domain\Model;

use App\Recruitment\Domain\Event\ApplicationEnriched;
use App\Recruitment\Domain\Event\ApplicationSubmitted;
use App\Recruitment\Domain\ValueObject\AiScore;
use App\Recruitment\Domain\ValueObject\ApplicationStatus;
use App\Recruitment\Domain\ValueObject\CvText;
use App\Recruitment\Domain\ValueObject\Email;
use App\Recruitment\Domain\ValueObject\FullName;
use App\Recruitment\Domain\ValueObject\Phone;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\Uuid;

final class JobApplication extends AggregateRoot
{
    private ApplicationStatus $status;
    private ?string $aiSummary = null;
    private ?AiScore $aiScore = null;
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $jobId,
        private readonly FullName $fullName,
        private readonly Email $email,
        private readonly ?Phone $phone,
        private readonly ?string $notes,
        private readonly CvText $cvText,
        private readonly \DateTimeImmutable $appliedAt,
    ) {
        $this->status = ApplicationStatus::Received;
        $this->updatedAt = $appliedAt;
    }

    public static function create(
        Uuid $id,
        Uuid $jobId,
        FullName $fullName,
        Email $email,
        ?Phone $phone,
        ?string $notes,
        CvText $cvText,
        \DateTimeImmutable $appliedAt,
    ): self {
        $application = new self($id, $jobId, $fullName, $email, $phone, $notes, $cvText, $appliedAt);

        $application->recordEvent(new ApplicationSubmitted(
            $id->value(),
            $jobId->value(),
            $cvText->value(),
            $appliedAt,
        ));

        return $application;
    }

    /**
     * Reconstructs an existing application from persistence. No domain events are recorded.
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $jobId,
        FullName $fullName,
        Email $email,
        ?Phone $phone,
        ?string $notes,
        CvText $cvText,
        \DateTimeImmutable $appliedAt,
        ApplicationStatus $status,
        ?string $aiSummary,
        ?AiScore $aiScore,
        \DateTimeImmutable $updatedAt,
    ): self {
        $application = new self($id, $jobId, $fullName, $email, $phone, $notes, $cvText, $appliedAt);
        $application->status = $status;
        $application->aiSummary = $aiSummary;
        $application->aiScore = $aiScore;
        $application->updatedAt = $updatedAt;

        return $application;
    }

    public function enrich(string $summary, AiScore $score, \DateTimeImmutable $now): void
    {
        if (ApplicationStatus::Enriched === $this->status) {
            // Idempotent: a redelivered ApplicationSubmitted message must not
            // overwrite the result or emit a duplicate ApplicationEnriched event.
            return;
        }

        $this->aiSummary = trim($summary);
        $this->aiScore = $score;
        $this->status = ApplicationStatus::Enriched;
        $this->updatedAt = $now;

        $this->recordEvent(new ApplicationEnriched($this->id->value(), $score->value(), $now));
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function jobId(): Uuid
    {
        return $this->jobId;
    }

    public function fullName(): FullName
    {
        return $this->fullName;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function phone(): ?Phone
    {
        return $this->phone;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function cvText(): CvText
    {
        return $this->cvText;
    }

    public function appliedAt(): \DateTimeImmutable
    {
        return $this->appliedAt;
    }

    public function status(): ApplicationStatus
    {
        return $this->status;
    }

    public function aiSummary(): ?string
    {
        return $this->aiSummary;
    }

    public function aiScore(): ?AiScore
    {
        return $this->aiScore;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
