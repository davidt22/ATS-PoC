<?php

declare(strict_types=1);

namespace App\Tests\Recruitment\UI\Http;

use App\Recruitment\Domain\Model\JobPosting;
use App\Recruitment\Domain\Repository\JobPostingRepositoryInterface;
use App\Recruitment\Domain\ValueObject\JobDescription;
use App\Recruitment\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApplyFlowTest extends WebTestCase
{
    use CleansRecruitmentTables;

    public function test_submitting_a_valid_application_redirects_to_its_detail_page(): void
    {
        $client = static::createClient();
        $this->cleanRecruitmentTables();

        $jobPostings = self::getContainer()->get(JobPostingRepositoryInterface::class);
        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $crawler = $client->request('GET', '/apply');
        $form = $crawler->selectButton('Enviar candidatura')->form([
            'jobId' => $jobId->value(),
            'fullName' => 'Ana García',
            'email' => 'ana@example.com',
            'phone' => '+34600111222',
            'notes' => '',
            'cvText' => str_repeat('Experiencia relevante en PHP. ', 5),
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
        self::assertStringStartsWith('/applications/', $client->getResponse()->headers->get('Location'));

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Ana García');
    }

    public function test_submitting_an_invalid_application_shows_errors_without_persisting(): void
    {
        $client = static::createClient();
        $this->cleanRecruitmentTables();

        $jobPostings = self::getContainer()->get(JobPostingRepositoryInterface::class);
        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $crawler = $client->request('GET', '/apply');
        $form = $crawler->selectButton('Enviar candidatura')->form([
            'jobId' => $jobId->value(),
            'fullName' => '',
            'email' => 'not-an-email',
            'cvText' => 'demasiado corto',
        ]);
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.error');
    }

    public function test_the_apply_page_exposes_job_descriptions_for_dynamic_display(): void
    {
        $client = static::createClient();
        $this->cleanRecruitmentTables();

        $jobPostings = self::getContainer()->get(JobPostingRepositoryInterface::class);
        $jobId = Uuid::generate();
        $jobPostings->save(JobPosting::create($jobId, new JobTitle('Backend Engineer'), new JobDescription('Great job')));

        $crawler = $client->request('GET', '/apply');

        self::assertResponseIsSuccessful();
        $descriptionsAttribute = $crawler->filter('[data-controller="job-description"]')
            ->attr('data-job-description-descriptions-value');
        self::assertNotNull($descriptionsAttribute);
        $descriptions = json_decode($descriptionsAttribute, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Great job', $descriptions[$jobId->value()]);
    }
}
