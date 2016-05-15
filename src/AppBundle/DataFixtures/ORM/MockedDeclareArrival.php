<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\DeclareArrival;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockedDeclareArrival implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DeclareArrival
     */
    static private $mockedArrival;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        //Create mocked data
        self::$mockedArrival = new DeclareArrival();

        // TODO: Fill mockedObject

        //Persist mocked data
        $manager->persist(self::$mockedArrival);
        $manager->flush();
    }

    /**
     *  The order in which fixtures will be loaded, the lower the number,
     *  the sooner that this fixture is loaded
     *
     * @return int
     */
    public function getOrder()
    {
        return 5;
    }

    /**
     * @return DeclareArrival
     */
    public static function getMockedArrival()
    {
        return self::$mockedArrival;
    }


}