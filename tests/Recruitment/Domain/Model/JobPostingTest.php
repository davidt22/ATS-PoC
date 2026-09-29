<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\Domain\Model;

use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class JobPostingTest extends TestCase
{
    public function test_create_sets_title_and_description(): void
    {
        $id = Uuid::generate();

        $jobPosting = JobPosting::create($id, new JobTitle('Backend Engineer'), new JobDescription('Great job'));

        self::assertTrue($id->equals($jobPosting->id()));
        self::assertSame('Backend Engineer', $jobPosting->title()->value());
        self::assertSame('Great job', $jobPosting->description()->value());
    }

    public function test_update_replaces_title_and_description(): void
    {
        $jobPosting = JobPosting::create(Uuid::generate(), new JobTitle('Backend Engineer'), new JobDescription('Great job'));

        $jobPosting->update(new JobTitle('Senior Backend Engineer'), new JobDescription('Even better job'));

        self::assertSame('Senior Backend Engineer', $jobPosting->title()->value());
        self::assertSame('Even better job', $jobPosting->description()->value());
    }
}
