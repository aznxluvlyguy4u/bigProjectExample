<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\DeclareArrivalResponse;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockedDeclareArrivalResponse implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DeclareArrivalResponse
     */
    static private $mockedArrivalResponse;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //Create mocked data
        self::$mockedArrivalResponse = new DeclareArrivalResponse();

        // TODO: Fill mockedObject

        //Persist mocked data
        $manager->persist(self::$mockedArrivalResponse);
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
        return 6;
    }

    /**
     * @return DeclareArrivalResponse
     */
    public static function getMockedArrivalResponse()
    {
        return self::$mockedArrivalResponse;
    }


}