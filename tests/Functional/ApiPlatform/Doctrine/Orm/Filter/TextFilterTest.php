<?php

namespace App\Tests\Functional\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

class TextFilterTest extends ApiTestCase
{
    private Client $client;
    private EntityManagerInterface $entityManager;
    private TestHandler $logger;

    protected function setUp(): void
    {
        $this->client           = static::createClient();
        $this->entityManager    = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->logger           = static::getContainer()->get('monolog.handler.testing');
    }

    public function testNotMappedProperty()
    {
        $response = $this->client->request('GET', 'api/employees?page=1&not_existing_property[exact]=TEST');
        $responseArr = $response->toArray();

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertSame(1000, $responseArr['hydra:totalItems']);
        self::assertIsArray($responseArr['hydra:member']);
    }

    public function testInvalidStrategy()
    {
        $response    = $this->client->request('GET', 'api/employees?page=1&firstname[not_existing_strategy]=TEST');
        $responseArr = $response->toArray();

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertSame(1000, $responseArr['hydra:totalItems']);
        self::assertIsArray($responseArr['hydra:member']);

        self::assertCount(1, array_filter(
            $this->logger->getRecords(),
            static fn(LogRecord $record) => $record->level->name === 'Notice' && $record->message === 'Invalid filter ignored' && $record->context['exception'] instanceof InvalidArgumentException && $record->context['exception']->getMessage() === 'At least one valid strategy ("exact", "partial") is required for "firstname" property'
        ));
    }

    public function testExactStrategy()
    {
        $employee = $this->entityManager->createQuery('SELECT e, RAND() as HIDDEN rand from App\Entity\Employee e ORDER BY rand')->setMaxResults(1)->getOneOrNullResult();
        self::assertInstanceOf(Employee::class, $employee);

        $response    = $this->client->request('GET', sprintf('api/employees?page=1&firstname[exact]=%s', mb_strtolower($employee->getFirstname())));
        $responseArr = $response->toArray();

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $results = $this->entityManager->createQuery('SELECT e from App\Entity\Employee e WHERE LOWER(e.firstname) = LOWER(:firstname)')->setParameters(['firstname' => $employee->getFirstname()])->getArrayResult();

        self::assertSame(count($results), $responseArr['hydra:totalItems']);
        self::assertIsArray($responseArr['hydra:member']);

        self::assertSame($results[0]['id'], $responseArr['hydra:member'][0]['id']);
        $lastIndex = min(29, count($results)-1);
        self::assertSame($results[$lastIndex]['id'], $responseArr['hydra:member'][$lastIndex]['id']);
    }

    public function testPartialStrategy()
    {
        $results = $this->entityManager->createQuery("SELECT e from App\Entity\Employee e WHERE LOWER(e.firstname) LIKE LOWER('%B%') ")->getArrayResult();

        $response    = $this->client->request('GET', 'api/employees?page=1&firstname[partial]=B');
        $responseArr = $response->toArray();

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $results = $this->entityManager
            ->createQuery('SELECT e from App\Entity\Employee e WHERE LOWER(e.firstname) LIKE LOWER(:firstname)')->setParameters(['firstname' => '%B%'])->getArrayResult()
        ;

        self::assertSame(count($results), $responseArr['hydra:totalItems']);
        self::assertIsArray($responseArr['hydra:member']);

        self::assertSame($results[0]['id'], $responseArr['hydra:member'][0]['id']);
        $lastIndex = min(29, count($results) - 1);
        self::assertSame($results[$lastIndex]['id'], $responseArr['hydra:member'][$lastIndex]['id']);
    }

    public function testNestedProperty()
    {
        $employee = $this->entityManager->createQuery('SELECT e, RAND() as HIDDEN rand from App\Entity\Employee e ORDER BY rand')->setMaxResults(1)->getOneOrNullResult();
        self::assertInstanceOf(Employee::class, $employee);

        $organizationPartialName = mb_strtoupper(mb_substr($employee->getOrganization()->getName(), 1, 3));
        $response    = $this->client->request('GET', sprintf('api/employees?page=1&organization.name[partial]=%s', $organizationPartialName));
        $responseArr = $response->toArray();

        $results = $this->entityManager
            ->createQuery('SELECT e from App\Entity\Employee e INNER JOIN e.organization o WHERE LOWER(o.name) LIKE LOWER(:organizationName)')->setParameters(['organizationName' => '%'.$organizationPartialName.'%'])->getArrayResult()
        ;

        self::assertSame(count($results), $responseArr['hydra:totalItems']);
        self::assertCount(min(30, count($results)), $responseArr['hydra:member']);
        self::assertSame($results[0]['id'], $responseArr['hydra:member'][0]['id']);
        $lastIndex = min(29, count($results) - 1);
        self::assertSame($results[$lastIndex]['id'], $responseArr['hydra:member'][$lastIndex]['id']);
    }
}
