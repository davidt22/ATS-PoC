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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JobPostingCrudTest extends WebTestCase
{
    use CleansRecruitmentTables;

    public function test_create_list_and_edit_a_job_posting(): void
    {
        $client = static::createClient();
        $this->cleanRecruitmentTables();

        $createCrawler = $client->request('GET', '/jobs/new');
        $createForm = $createCrawler->selectButton('Crear oferta')->form([
            'title' => 'Backend Engineer',
            'description' => 'Great job',
        ]);
        $client->submit($createForm);
        self::assertResponseRedirects('/');

        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Backend Engineer');

        $jobPostings = self::getContainer()->get(JobPostingRepositoryInterface::class);
        $jobPosting = $jobPostings->findAll()[0];

        $editCrawler = $client->request('GET', '/jobs/'.$jobPosting->id()->value().'/edit');
        $editForm = $editCrawler->selectButton('Guardar cambios')->form([
            'title' => 'Senior Backend Engineer',
            'description' => 'Even greater job',
        ]);
        $client->submit($editForm);
        self::assertResponseRedirects('/');

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $updated = $jobPostings->findById($jobPosting->id());
        self::assertSame('Senior Backend Engineer', $updated->title()->value());
    }

    public function test_deleting_a_job_posting_with_applications_is_blocked(): void
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

        $crawler = $client->request('GET', '/');
        $client->submit($crawler->selectButton('Eliminar')->form());

        self::assertResponseRedirects('/');
        self::assertNotNull($jobPostings->findById($jobId));
    }

    public function test_deleting_a_job_posting_without_applications_succeeds(): void
    {
        $client = static::createClient();
        $this->cleanRecruitmentTables();

        $jobPostings = self::getContainer()->get(JobPostingRepositoryInterface::class);
        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $crawler = $client->request('GET', '/');
        $client->submit($crawler->selectButton('Eliminar')->form());

        self::assertResponseRedirects('/');
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull($jobPostings->findById($jobId));
    }
}
