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
use AppBundle\JsonFormat\DeclareArrivalJsonFormat;
use AppBundle\Service\IRSerializer;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

class MockedDeclareArrival implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    const DECLARE_ARRIVAL_ENDPOINT = "/api/v1/arrivals";

    static public $hasCascadePersistenceIssueBeenFixed = false;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RequestClient
     */
    private $client;

    /**
     * @var IRSerializer
     */
    static private $serializer;

    /**
     * @var EntityManager
     */
    static private $entityManager;

    /**
     * @var DeclareArrival
     */
    static private $mockedArrival;

    /**
     * @var array
     */
    private $defaultHeaders;

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

        } else {

            //Get service classes
            self::$serializer = $this->container->get('app.serializer.ir');
            self::$entityManager = $this->container->get('doctrine.orm.entity_manager');

            //Create client
            $this->client = new RequestClient($this->container->get('kernel'));

            $this->defaultHeaders = array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCESSTOKEN' => MockedClient::getMockedClient()->getAccessToken(),
            );

            //Create declare arrival
            $declareArrivalJsonFormat = new DeclareArrivalJsonFormat();
            $declareArrivalJsonFormat->setArrivalDate(new \DateTime());
            $declareArrivalJsonFormat->setUbnPreviousOwner("123456");
            $declareArrivalJsonFormat->setIsImportAnimal(false);
            $declareArrivalJsonFormat->setAnimal(MockedAnimal::getMockedRamWithParents());

            //Create json to be posted
            $declareArrivalJson = self::$serializer->serializeToJSON($declareArrivalJsonFormat);

            $this->client->request('POST',
                $this::DECLARE_ARRIVAL_ENDPOINT,
                array(),
                array(),
                $this->defaultHeaders,
                $declareArrivalJson
            );

            $response = $this->client->getResponse()->getContent();
            $declareArrivalArray = json_decode($response, true);
            $id = $declareArrivalArray['id'];

            self::$mockedArrival = self::$entityManager->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->findOneBy(array("id"=>$id));
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