<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;

/**
 * Class LoadMockAnimals
 * @package AppBundle\DataFixtures\ORM
 *
 * @see <a href="http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html">
 *     DoctrineFixturesBundle</a>
 */
class LoadMockAnimals implements FixtureInterface
{
    public function load(ObjectManager $manager){
        $father = new Ram();
        $father->setUlnCountryCode("NL");
        $father->setUlnNumber("00001");
        $father->setAnimalType(1);

        $mother = new Ewe();
        $mother->setUlnCountryCode("NL");
        $mother->setUlnNumber("00002");
        $mother->setAnimalType(2);

        $child = new Ram();
        $child->setUlnCountryCode("NL");
        $child->setUlnNumber("1234566");
        $child->setAnimalType(1);
        $child->setDateOfBirth(new \DateTime());
        $child->setParentFather($father);
        $child->setParentMother($mother);

        $manager->persist($child);
        $manager->flush();
    }

    public function getOrder()
    {
        // the order in which fixtures will be loaded
        // the lower the number, the sooner that this fixture is loaded
        return 2;
    }
}