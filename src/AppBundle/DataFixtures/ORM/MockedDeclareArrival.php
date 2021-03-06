<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\JsonFormat\DeclareArrivalJsonFormat;
use AppBundle\Service\IRSerializer;
use AppBundle\Setting\DataFixtureSetting;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * @var ObjectManager
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
        if(!DataFixtureSetting::USE_MOCKED_REQUESTS_AND_RESPONSES) {
            return null;
        }

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
            self::$mockedArrival->setRequestState(RequestStateType::FINISHED);
            self::$mockedArrival->setAction('C');
            self::$mockedArrival->setRecoveryIndicator('N');
            self::$mockedArrival->setRelationNumberKeeper($relationNumberKeeper);

            //Persist mocked data
            $manager->persist(self::$mockedArrival);
            $manager->flush();

        } else {

            //Get service classes
            self::$serializer = $this->container->get('app.serializer.ir');
            self::$entityManager = $this->container->get('doctrine')->getManager();

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

            self::$mockedArrival = self::$entityManager->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->findAll()['0'];

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