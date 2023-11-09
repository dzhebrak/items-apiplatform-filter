<?php

namespace App\Tests\Integration\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Test\DoctrineOrmFilterTestCase;
use App\ApiPlatform\Doctrine\Orm\Filter\TextFilter;
use App\Entity\Employee;

/**
 * Internal class is used for simplification
 */
class TextFilterTest extends DoctrineOrmFilterTestCase
{
    protected string $filterClass = TextFilter::class;
    protected const ALIAS = 'e';

    protected function setUp(): void
    {
        self::bootKernel();

        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository      = $this->managerRegistry->getManagerForClass(Employee::class)->getRepository(Employee::class);
    }

    public static function provideApplyTestData(): array
    {
        return [
            [
                [], [], 'SELECT e FROM App\Entity\Employee e', [], null, Employee::class,
            ],

            [
                ['firstname' => null], ['firstname' => ['exact' => 'TEST']], 'SELECT e FROM App\Entity\Employee e WHERE LOWER(e.firstname) = LOWER(:firstname_p1)', [], null, Employee::class,
            ],

            [
                ['firstname' => null], ['firstname' => ['partial' => 'TEST']], 'SELECT e FROM App\Entity\Employee e WHERE LOWER(e.firstname) LIKE LOWER(:firstname_p1)', [], null, Employee::class,
            ],

            [
                ['firstname' => null], ['firstname' => ['not_existing_strategy' => 'TEST']], 'SELECT e FROM App\Entity\Employee e', [], null, Employee::class,
            ],

            [
                ['organization.name' => null], ['organization.name' => ['exact' => 'TEST']], 'SELECT e FROM App\Entity\Employee e INNER JOIN e.organization organization_a1 WHERE LOWER(organization_a1.name) = LOWER(:name_p1)', [], null, Employee::class,
            ],

            [
                ['organization.name' => null], ['organization.name' => ['partial' => 'TEST']], 'SELECT e FROM App\Entity\Employee e INNER JOIN e.organization organization_a1 WHERE LOWER(organization_a1.name) LIKE LOWER(:name_p1)', [], null, Employee::class,
            ],
        ];
    }
}
