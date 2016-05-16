<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockedDeclareArrival implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    static public $hasCascadePersistenceIssueBeenFixed = false;

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
        if(self::$hasCascadePersistenceIssueBeenFixed) {
            //Create mocked data

            //DeclareArrival
            self::$mockedArrival = new DeclareArrival();

            $animal = MockedAnimal::getMockedAnotherEwe();
            $client = MockedClient::getMockedClient();
            $location = MockedClient::getMockedLocation();
            $id = uniqid(mt_rand(0,999999));
            $relationNumberKeeper = $client->getRelationNumberKeeper();

            self::$mockedArrival->setAnimal($animal);
            self::$mockedArrival->setArrivalDate(new \DateTime('2023-03-20'));
            self::$mockedArrival->setUbnPreviousOwner("48624");
            self::$mockedArrival->setIsImportAnimal(false);
            self::$mockedArrival->setLocation($location);
            self::$mockedArrival->setLogDate(new \DateTime('2023-03-22'));
            self::$mockedArrival->setRequestId($id);
            self::$mockedArrival->setMessageId($id);
            self::$mockedArrival->setRequestState('open');
            self::$mockedArrival->setAction('C');
            self::$mockedArrival->setRecoveryIndicator('N');
            self::$mockedArrival->setRelationNumberKeeper($relationNumberKeeper);

            //Persist mocked data
            $manager->persist(self::$mockedArrival);
            $manager->flush();
        }
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