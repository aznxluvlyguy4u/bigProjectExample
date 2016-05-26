<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareDepartResponse;
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

class MockedDeclareDepart implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    const DECLARE_DEPART_ENDPOINT = "/api/v1/departs";

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
     * @var DeclareDepart
     */
    static private $mockedDepart;

    /**
     * @var DeclareDepartResponse
     */
    static private $mockedDepartSuccessResponse;

    /**
     * @var DeclareDepartResponse
     */
    static private $mockedDepartFailedResponse;

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
        $departRepository = self::$entityManager->getRepository(Constant::DECLARE_DEPART_REPOSITORY);

        //Create client
        $this->client = new RequestClient($this->container->get('kernel'));

        $this->defaultHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => MockedClient::getMockedClient()->getAccessToken(),
        );

        $ram = MockedAnimal::getMockedParentRam();

        //Create declare depart
        $declareDepartArray = array(
            "reason_of_depart" => "a very good reason",
            "ubn_new_owner" => "123456",
            "is_export_animal"  =>  false,
            "animal" => array(
                "uln_country_code" => $ram->getUlnCountryCode(),
                "uln_number" => $ram->getUlnNumber(),
                "pedigree_country_code" => $ram->getPedigreeCountryCode(),
                "pedigree_number" => $ram->getPedigreeNumber()
            ),
            "depart_date" => "2012-04-21T18:25:43-05:00"
        );

        $declareDepartJson = json_encode($declareDepartArray);

        $this->client->request('POST',
            $this::DECLARE_DEPART_ENDPOINT,
            array(),
            array(),
            $this->defaultHeaders,
            $declareDepartJson
        );

        $response = $this->client->getResponse()->getContent();
        $declareDepartArray = json_decode($response, true);

        self::$mockedDepart = $departRepository->findAll()['0'];

        //Create Responses
        $messageNumberFailedResponse = mt_rand(0,99999999999999);
        $messageNumberSuccessResponse = mt_rand(0,99999999999999);

        //It is necessary to retrieve the DeclareDepart entity from the Repository first
        $declareDepart = $departRepository->findAll()['0'];

        //Create and persist failed Response
        self::$mockedDepartFailedResponse = new DeclareDepartResponse();
        self::$mockedDepartFailedResponse->setDeclareDepartRequestMessage($declareDepart);
        self::$mockedDepartFailedResponse->setLogDate(new \DateTime('2023-03-21'));
        self::$mockedDepartFailedResponse->setRequestId($declareDepart->getRequestId());
        self::$mockedDepartFailedResponse->setMessageId($declareDepart->getMessageId());
        self::$mockedDepartFailedResponse->setMessageNumber($messageNumberFailedResponse);
        self::$mockedDepartFailedResponse->setDepartDate($declareDepart->getDepartDate());
        self::$mockedDepartFailedResponse->setUbnNewOwner($declareDepart->getUbnNewOwner());
        self::$mockedDepartFailedResponse->setErrorCode("IRD-00363");
        self::$mockedDepartFailedResponse->setErrorMessage("Er zijn geen dieren gevonden bij het opgegeven werknummer");
        self::$mockedDepartFailedResponse->setErrorKindIndicator("F");
        self::$mockedDepartFailedResponse->setSuccessIndicator("N");
        //Worker logic
        self::$mockedDepartFailedResponse->setUlnCountryCode($declareDepart->getUlnCountryCode());
        self::$mockedDepartFailedResponse->setUlnNumber($declareDepart->getUlnNumber());
        self::$mockedDepartFailedResponse->setPedigreeCountryCode($declareDepart->getPedigreeCountryCode());
        self::$mockedDepartFailedResponse->setPedigreeNumber($declareDepart->getPedigreeNumber());
        //status changes
        self::$mockedDepartFailedResponse->setIsExportAnimal(false);
        self::$mockedDepartFailedResponse->setIsRemovedByUser(false);

        //Create and persist successful Response
        self::$mockedDepartSuccessResponse = new DeclareDepartResponse();
        self::$mockedDepartSuccessResponse->setDeclareDepartRequestMessage($declareDepart);
        self::$mockedDepartSuccessResponse->setLogDate(new \DateTime('2023-03-22'));
        self::$mockedDepartSuccessResponse->setRequestId($declareDepart->getRequestId());
        self::$mockedDepartSuccessResponse->setMessageId($declareDepart->getMessageId());
        self::$mockedDepartSuccessResponse->setMessageNumber($messageNumberSuccessResponse);
        self::$mockedDepartSuccessResponse->setDepartDate($declareDepart->getDepartDate());
        self::$mockedDepartSuccessResponse->setUbnNewOwner($declareDepart->getUbnNewOwner());
        self::$mockedDepartSuccessResponse->setErrorCode(null);
        self::$mockedDepartSuccessResponse->setErrorMessage(null);
        self::$mockedDepartSuccessResponse->setErrorKindIndicator(null);
        self::$mockedDepartSuccessResponse->setSuccessIndicator("J");
        //Worker logic
        self::$mockedDepartSuccessResponse->setUlnCountryCode($declareDepart->getUlnCountryCode());
        self::$mockedDepartSuccessResponse->setUlnNumber($declareDepart->getUlnNumber());
        self::$mockedDepartSuccessResponse->setPedigreeCountryCode($declareDepart->getPedigreeCountryCode());
        self::$mockedDepartSuccessResponse->setPedigreeNumber($declareDepart->getPedigreeNumber());
        //status changes
        self::$mockedDepartSuccessResponse->setIsExportAnimal(true);
        self::$mockedDepartSuccessResponse->setIsRemovedByUser(false);

        //Persist mocked data
        $manager->persist(self::$mockedDepartSuccessResponse);
        $manager->persist(self::$mockedDepartFailedResponse);
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
     * @return DeclareDepart
     */
    public static function getMockedDepart()
    {
        return self::$mockedDepart;
    }

    /**
     * @return DeclareDepartResponse
     */
    public static function getMockedDepartSuccessResponse()
    {
        return self::$mockedDepartSuccessResponse;
    }

    /**
     * @return DeclareDepartResponse
     */
    public static function getMockedDepartFailedResponse()
    {
        return self::$mockedDepartFailedResponse;
    }

}