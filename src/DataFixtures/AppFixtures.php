<?php

namespace App\DataFixtures;

use App\Factory\EmployeeFactory;
use App\Factory\OrganizationFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        OrganizationFactory::createMany(100);
        EmployeeFactory::createMany(1000);
    }
}
