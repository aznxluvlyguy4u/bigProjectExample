<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Enumerator\AnimalType;
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
        $father->setAnimalType(AnimalType::sheep);

        $mother = new Ewe();
        $mother->setUlnCountryCode("NL");
        $mother->setUlnNumber("00002");
        $mother->setAnimalType(AnimalType::sheep);

        $child = new Ram();
        $child->setUlnCountryCode("UK");
        $child->setUlnNumber("12345");
        $child->setPedigreeNumber("12345");
        $child->setPedigreeCountryCode("NL");
        $child->setAnimalType(AnimalType::sheep);
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