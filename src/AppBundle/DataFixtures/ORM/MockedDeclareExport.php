<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareExportResponse;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Service\IRSerializer;
use AppBundle\Setting\DataFixtureSetting;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\JsonResponse;

class MockedDeclareExport implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    const DECLARE_EXPORT_ENDPOINT = "/api/v1/departs";

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
     * @var DeclareExport
     */
    static private $mockedExport;

    /**
     * @var DeclareExportResponse
     */
    static private $mockedExportSuccessResponse;

    /**
     * @var DeclareExportResponse
     */
    static private $mockedExportFailedResponse;

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

        //Get service classes
        self::$serializer = $this->container->get('app.serializer.ir');
        self::$entityManager = $this->container->get('doctrine.orm.entity_manager');
        $exportRepository = self::$entityManager->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);

        //Create client
        $this->client = new RequestClient($this->container->get('kernel'));

        $this->defaultHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => MockedClient::getMockedClient()->getAccessToken(),
        );

        $ewe = MockedAnimal::getMockedParentEwe();

        //Create declare export
        $declareExportArray = array(
            "reason_of_depart" => "a very good reason",
            "is_export_animal" => true,
            "depart_date" => "2016-05-26T18:25:43-05:00",
            "animal" => array(
                "uln_country_code" => $ewe->getUlnCountryCode(),
                "uln_number" => $ewe->getUlnNumber()
            ),
        );

        $declareExportJson = json_encode($declareExportArray);

        $this->client->request('POST',
            $this::DECLARE_EXPORT_ENDPOINT,
            array(),
            array(),
            $this->defaultHeaders,
            $declareExportJson
        );

        $response = $this->client->getResponse()->getContent();
        $declareExportArray = json_decode($response, true);

        self::$mockedExport = $exportRepository->findAll()['0'];

        //Create Responses
        $messageNumberFailedResponse = mt_rand(0,99999999999999);
        $messageNumberSuccessResponse = mt_rand(0,99999999999999);

        //It is necessary to retrieve the DeclareExport entity from the Repository first
        $declareExport = $exportRepository->findAll()['0'];
        $animal = $declareExport->getUlnCountryCode();

        //Create and persist failed Response
        self::$mockedExportFailedResponse = new DeclareExportResponse();
        self::$mockedExportFailedResponse->setDeclareExportRequestMessage($declareExport);
        self::$mockedExportFailedResponse->setLogDate(new \DateTime('2023-03-21'));
        self::$mockedExportFailedResponse->setRequestId($declareExport->getRequestId());
        self::$mockedExportFailedResponse->setMessageId($declareExport->getMessageId());
        self::$mockedExportFailedResponse->setMessageNumber($messageNumberFailedResponse);
        self::$mockedExportFailedResponse->setExportDate($declareExport->getExportDate());
        self::$mockedExportFailedResponse->setReasonOfExport($declareExport->getReasonOfExport());
        self::$mockedExportFailedResponse->setUlnCountryCode($declareExport->getUlnCountryCode());
        self::$mockedExportFailedResponse->setUlnNumber($declareExport->getUlnNumber());
        self::$mockedExportFailedResponse->setPedigreeCountryCode($declareExport->getPedigreeCountryCode());
        self::$mockedExportFailedResponse->setPedigreeNumber($declareExport->getPedigreeNumber());
        self::$mockedExportFailedResponse->setErrorCode("IRD-00363");
        self::$mockedExportFailedResponse->setErrorMessage("Er zijn geen dieren gevonden bij het opgegeven werknummer");
        self::$mockedExportFailedResponse->setErrorKindIndicator("F");
        self::$mockedExportFailedResponse->setSuccessIndicator("N");
        self::$mockedExportFailedResponse->setIsExportAnimal(true);
        //Worker logic
                //-
        //status changes
        self::$mockedExportFailedResponse->setIsRemovedByUser(false);

        //Create and persist successful Response
        self::$mockedExportSuccessResponse = new DeclareExportResponse();
        self::$mockedExportSuccessResponse->setDeclareExportRequestMessage($declareExport);
        self::$mockedExportSuccessResponse->setLogDate(new \DateTime('2023-03-22'));
        self::$mockedExportSuccessResponse->setRequestId($declareExport->getRequestId());
        self::$mockedExportSuccessResponse->setMessageId($declareExport->getMessageId());
        self::$mockedExportSuccessResponse->setMessageNumber($messageNumberSuccessResponse);
        self::$mockedExportSuccessResponse->setExportDate($declareExport->getExportDate());
        self::$mockedExportSuccessResponse->setReasonOfExport($declareExport->getReasonOfExport());
        self::$mockedExportSuccessResponse->setUlnCountryCode($declareExport->getUlnCountryCode());
        self::$mockedExportSuccessResponse->setUlnNumber($declareExport->getUlnNumber());
        self::$mockedExportSuccessResponse->setPedigreeCountryCode($declareExport->getPedigreeCountryCode());
        self::$mockedExportSuccessResponse->setPedigreeNumber($declareExport->getPedigreeNumber());
        self::$mockedExportSuccessResponse->setErrorCode(null);
        self::$mockedExportSuccessResponse->setErrorMessage(null);
        self::$mockedExportSuccessResponse->setErrorKindIndicator(null);
        self::$mockedExportSuccessResponse->setSuccessIndicator("J");
        self::$mockedExportSuccessResponse->setIsExportAnimal(true);
        //Worker logic
                //-
        //status changes
        self::$mockedExportSuccessResponse->setIsRemovedByUser(false);

        //Persist mocked data
        $manager->persist(self::$mockedExportSuccessResponse);
        $manager->persist(self::$mockedExportFailedResponse);
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
     * @return DeclareExport
     */
    public static function getMockedExport()
    {
        return self::$mockedExport;
    }

    /**
     * @return DeclareExportResponse
     */
    public static function getMockedExportSuccessResponse()
    {
        return self::$mockedExportSuccessResponse;
    }

    /**
     * @return DeclareExportResponse
     */
    public static function getMockedExportFailedResponse()
    {
        return self::$mockedExportFailedResponse;
    }

}