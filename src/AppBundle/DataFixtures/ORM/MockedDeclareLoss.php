<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareLossResponse;
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

class MockedDeclareLoss implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    const DECLARE_LOSS_ENDPOINT = "/api/v1/losses";

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
     * @var DeclareLoss
     */
    static private $mockedLoss;

    /**
     * @var DeclareLossResponse
     */
    static private $mockedLossSuccessResponse;

    /**
     * @var DeclareLossResponse
     */
    static private $mockedLossFailedResponse;

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
        $departRepository = self::$entityManager->getRepository(Constant::DECLARE_LOSS_REPOSITORY);

        //Create client
        $this->client = new RequestClient($this->container->get('kernel'));

        $this->defaultHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => MockedClient::getMockedClient()->getAccessToken(),
        );

        $ewe = MockedAnimal::getMockedParentEwe();

        //Create declare depart
        $declareLossArray = array(
            "reason_of_loss" => "a very delicious reason",
            "date_of_death" => "2016-06-21T18:25:43-05:00",
            "animal" => array(
                "uln_country_code" => $ewe->getUlnCountryCode(),
                "uln_number" => $ewe->getUlnNumber(),
                "gender" => $ewe->getGender()
            ),
        );

        $declareLossJson = json_encode($declareLossArray);

        $this->client->request('POST',
            $this::DECLARE_LOSS_ENDPOINT,
            array(),
            array(),
            $this->defaultHeaders,
            $declareLossJson
        );

        $response = $this->client->getResponse()->getContent();
        $declareLossArray = json_decode($response, true);

        self::$mockedLoss = $departRepository->findAll()['0'];

        //Create Responses
        $messageNumberFailedResponse = mt_rand(0,99999999999999);
        $messageNumberSuccessResponse = mt_rand(0,99999999999999);

        //It is necessary to retrieve the DeclareLoss entity from the Repository first
        $declareLoss = $departRepository->findAll()['0'];

        //Create and persist failed Response
        self::$mockedLossFailedResponse = new DeclareLossResponse();
        self::$mockedLossFailedResponse->setDeclareLossRequestMessage($declareLoss);
        self::$mockedLossFailedResponse->setLogDate(new \DateTime('2023-03-21'));
        self::$mockedLossFailedResponse->setRequestId($declareLoss->getRequestId());
        self::$mockedLossFailedResponse->setMessageId($declareLoss->getMessageId());
        self::$mockedLossFailedResponse->setMessageNumber($messageNumberFailedResponse);
        self::$mockedLossFailedResponse->setDateOfDeath($declareLoss->getDateOfDeath());
        self::$mockedLossFailedResponse->setReasonOfLoss($declareLoss->getReasonOfLoss());
        self::$mockedLossFailedResponse->setErrorCode("IRD-00363");
        self::$mockedLossFailedResponse->setErrorMessage("Er zijn geen dieren gevonden bij het opgegeven werknummer");
        self::$mockedLossFailedResponse->setErrorKindIndicator("F");
        self::$mockedLossFailedResponse->setSuccessIndicator("N");
        //Worker logic
        self::$mockedLossFailedResponse->setUlnCountryCode($declareLoss->getUlnCountryCode());
        self::$mockedLossFailedResponse->setUlnNumber($declareLoss->getUlnNumber());
        //status changes
        self::$mockedLossFailedResponse->setIsRemovedByUser(false);

        //Create and persist successful Response
        self::$mockedLossSuccessResponse = new DeclareLossResponse();
        self::$mockedLossSuccessResponse->setDeclareLossRequestMessage($declareLoss);
        self::$mockedLossSuccessResponse->setLogDate(new \DateTime('2023-03-22'));
        self::$mockedLossSuccessResponse->setRequestId($declareLoss->getRequestId());
        self::$mockedLossSuccessResponse->setMessageId($declareLoss->getMessageId());
        self::$mockedLossSuccessResponse->setMessageNumber($messageNumberSuccessResponse);
        self::$mockedLossSuccessResponse->setDateOfDeath($declareLoss->getDateOfDeath());
        self::$mockedLossSuccessResponse->setReasonOfLoss($declareLoss->getReasonOfLoss());
        self::$mockedLossSuccessResponse->setErrorCode(null);
        self::$mockedLossSuccessResponse->setErrorMessage(null);
        self::$mockedLossSuccessResponse->setErrorKindIndicator(null);
        self::$mockedLossSuccessResponse->setSuccessIndicator("J");
        //Worker logic
        self::$mockedLossSuccessResponse->setUlnCountryCode($declareLoss->getUlnCountryCode());
        self::$mockedLossSuccessResponse->setUlnNumber($declareLoss->getUlnNumber());
        //status changes
        self::$mockedLossSuccessResponse->setIsRemovedByUser(false);

        //Persist mocked data
        $manager->persist(self::$mockedLossSuccessResponse);
        $manager->persist(self::$mockedLossFailedResponse);
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
     * @return DeclareLoss
     */
    public static function getMockedLoss()
    {
        return self::$mockedLoss;
    }

    /**
     * @return DeclareLossResponse
     */
    public static function getMockedLossSuccessResponse()
    {
        return self::$mockedLossSuccessResponse;
    }

    /**
     * @return DeclareLossResponse
     */
    public static function getMockedLossFailedResponse()
    {
        return self::$mockedLossFailedResponse;
    }

}