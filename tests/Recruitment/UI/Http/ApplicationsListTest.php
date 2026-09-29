<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\UI\Http;

use App\Recruitment\Domain\Model\JobApplication;
use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\Repository\JobApplicationRepositoryInterface;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\ValueObject\CvText;
use App\Recruitment\Domain\ValueObject\Email;
use App\Recruitment\Domain\ValueObject\FullName;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApplicationsListTest extends WebTestCase
{
    use CleansRecruitmentTables;

    public function test_it_lists_and_searches_applications_via_the_json_api(): void
    {
        $client = static::createClient();
        $this->cleanRecruitmentTables();

        $jobPostings = self::getContainer()->get(JobPostingRepositoryInterface::class);
        $applications = self::getContainer()->get(JobApplicationRepositoryInterface::class);

        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $applications->save(JobApplication::create(
            Uuid::generate(),
            $jobId,
            new FullName('Ana García'),
            new Email('ana@example.com'),
            null,
            null,
            new CvText(str_repeat('a', 60)),
            new \DateTimeImmutable(),
        ));
        $applications->save(JobApplication::create(
            Uuid::generate(),
            $jobId,
            new FullName('Luis Pérez'),
            new Email('luis@example.com'),
            null,
            null,
            new CvText(str_repeat('b', 60)),
            new \DateTimeImmutable(),
        ));

        $client->request('GET', '/api/applications');
        self::assertResponseIsSuccessful();
        $all = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $all);

        $client->request('GET', '/api/applications?search=ana');
        $filtered = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $filtered);
        self::assertSame('ana@example.com', $filtered[0]['email']);
    }

    public function test_detail_returns_404_for_an_unknown_application(): void
    {
        $client = static::createClient();
        $this->cleanRecruitmentTables();

        $client->request('GET', '/applications/'.Uuid::generate()->value());

        self::assertResponseStatusCodeSame(404);
    }
}
