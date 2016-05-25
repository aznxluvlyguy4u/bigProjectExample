<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareImportResponse;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Service\IRSerializer;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\JsonResponse;

class MockedDeclareImport implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    const DECLARE_IMPORT_ENDPOINT = "/api/v1/arrivals";

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
     * @var DeclareImport
     */
    static private $mockedImport;

    /**
     * @var DeclareImportResponse
     */
    static private $mockedImportSuccessResponse;

    /**
     * @var DeclareImportResponse
     */
    static private $mockedImportFailedResponse;

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
        //Get service classes
        self::$serializer = $this->container->get('app.serializer.ir');
        self::$entityManager = $this->container->get('doctrine.orm.entity_manager');
        $departRepository = self::$entityManager->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);

        //Create client
        $this->client = new RequestClient($this->container->get('kernel'));

        $this->defaultHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => MockedClient::getMockedClient()->getAccessToken(),
        );

        $ewe = MockedAnimal::getMockedParentEwe();

        //Create declare depart
        $declareImportArray = array(
            "is_import_animal" => true,
            "country_origin" => "UK",
            "ubn_previous_owner" => "6166816",
            "animal" => array(
                "uln_country_code" => $ewe->getUlnCountryCode(),
                "uln_number" => $ewe->getUlnNumber()
            ),
        );

        $declareImportJson = json_encode($declareImportArray);

        $this->client->request('POST',
            $this::DECLARE_IMPORT_ENDPOINT,
            array(),
            array(),
            $this->defaultHeaders,
            $declareImportJson
        );

        $response = $this->client->getResponse()->getContent();
        $declareImportArray = json_decode($response, true);

        self::$mockedImport = $departRepository->findAll()['0'];

        //Create Responses
        $messageNumberFailedResponse = uniqid(mt_rand(0,99));
        $messageNumberSuccessResponse = uniqid(mt_rand(0,99));

        //It is necessary to retrieve the DeclareImport entity from the Repository first
        $declareImport = $departRepository->findAll()['0'];

        //Create and persist failed Response
        self::$mockedImportFailedResponse = new DeclareImportResponse();
        self::$mockedImportFailedResponse->setDeclareImportRequestMessage($declareImport);
        self::$mockedImportFailedResponse->setLogDate(new \DateTime('2023-03-21'));
        self::$mockedImportFailedResponse->setRequestId($declareImport->getRequestId());
        self::$mockedImportFailedResponse->setMessageId($declareImport->getMessageId());
        self::$mockedImportFailedResponse->setMessageNumber($messageNumberFailedResponse);
        self::$mockedImportFailedResponse->setImportDate($declareImport->getImportDate());
        self::$mockedImportFailedResponse->setAnimalCountryOrigin($declareImport->getAnimalCountryOrigin());
        self::$mockedImportFailedResponse->setErrorCode("IRD-00363");
        self::$mockedImportFailedResponse->setErrorMessage("Er zijn geen dieren gevonden bij het opgegeven werknummer");
        self::$mockedImportFailedResponse->setErrorKindIndicator("F");
        self::$mockedImportFailedResponse->setSuccessIndicator("N");
        //Worker logic
                //-
        //status changes
        self::$mockedImportFailedResponse->setIsRemovedByUser(false);

        //Create and persist successful Response
        self::$mockedImportSuccessResponse = new DeclareImportResponse();
        self::$mockedImportSuccessResponse->setDeclareImportRequestMessage($declareImport);
        self::$mockedImportSuccessResponse->setLogDate(new \DateTime('2023-03-22'));
        self::$mockedImportSuccessResponse->setRequestId($declareImport->getRequestId());
        self::$mockedImportSuccessResponse->setMessageId($declareImport->getMessageId());
        self::$mockedImportSuccessResponse->setMessageNumber($messageNumberSuccessResponse);
        self::$mockedImportSuccessResponse->setImportDate($declareImport->getImportDate());
        self::$mockedImportSuccessResponse->setAnimalCountryOrigin($declareImport->getAnimalCountryOrigin());
        self::$mockedImportSuccessResponse->setErrorCode(null);
        self::$mockedImportSuccessResponse->setErrorMessage(null);
        self::$mockedImportSuccessResponse->setErrorKindIndicator(null);
        self::$mockedImportSuccessResponse->setSuccessIndicator("J");
        //Worker logic
                //-
        //status changes
        self::$mockedImportSuccessResponse->setIsRemovedByUser(false);

        //Persist mocked data
        $manager->persist(self::$mockedImportSuccessResponse);
        $manager->persist(self::$mockedImportFailedResponse);
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
     * @return DeclareImport
     */
    public static function getMockedImport()
    {
        return self::$mockedImport;
    }

    /**
     * @return DeclareImportResponse
     */
    public static function getMockedImportSuccessResponse()
    {
        return self::$mockedImportSuccessResponse;
    }

    /**
     * @return DeclareImportResponse
     */
    public static function getMockedImportFailedResponse()
    {
        return self::$mockedImportFailedResponse;
    }

}