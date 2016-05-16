<?php

namespace AppBundle\Tests\Controller;


use AppBundle\DataFixtures\ORM\MockedDeclareArrival;
use AppBundle\DataFixtures\ORM\MockedDeclareArrivalResponse;
use AppBundle\DataFixtures\ORM\MockedTags;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareArrivalResponse;
use AppBundle\JsonFormat\RevokeDeclarationJsonFormat;
use AppBundle\Service\IRSerializer;
use AppBundle\DataFixtures\ORM\MockedAnimal;
use AppBundle\DataFixtures\ORM\MockedClient;
use AppBundle\Tests\TestSettings;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RevokeAPIControllerTest
 * @package AppBundle\Tests\Controller
 * @group revoke
 */
class RevokeAPIControllerTest extends WebTestCase
{
    const REVOKE_DECLARATION_ENDPOINT = "/api/v1/revokes";

    /**
     * @var RequestClient
     */
    private $client;

    /**
     * @var array
     */
    private $defaultHeaders;

    /**
     * @var IRSerializer
     */
    static private $serializer;

    /**
     * @var Client
     */
    static private $mockedClient;

    /**
     * @var DeclareArrival
     */
    static private $mockedArrival;

    /**
     * @var DeclareArrivalResponse
     */
    static private $mockedFailedResponse;

    /**
     * @var DeclareArrivalResponse
     */
    static private $mockedSuccessResponse;

    /**
     * Runs before class setup
     */
    public static function setUpBeforeClass()
    {
        //start the symfony kernel
        $kernel = static::createKernel();
        $kernel->boot();

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        //Get the DI container
        $container = $kernel->getContainer();

        //Get service classes
        self::$serializer = $container->get('app.serializer.ir');
    }

    /**
     * Runs on each testcase
     */
    public function setUp()
    {
        $this->client = parent::createClient();

        //Load fixture class
        /* Note! MockedTags and MockedAnimal must be explicitly loaded,
        or MockedDeclareArrival and MockedDeclareArrivalResponse
        cannot be created. */
        $fixtures = array(
            'AppBundle\DataFixtures\ORM\MockedClient',
            'AppBundle\DataFixtures\ORM\MockedTags',
            'AppBundle\DataFixtures\ORM\MockedAnimal',
            'AppBundle\DataFixtures\ORM\MockedDeclareArrival',
            'AppBundle\DataFixtures\ORM\MockedDeclareArrivalResponse');
        $this->loadFixtures($fixtures);

        //Get mocked Client
        self::$mockedClient = MockedClient::getMockedClient();
        $this->accessToken = self::$mockedClient->getAccessToken();

        //Get mocked messages
        self::$mockedArrival = MockedDeclareArrival::getMockedArrival();
        self::$mockedFailedResponse = MockedDeclareArrivalResponse::getMockedArrivalFailedResponse();
        self::$mockedSuccessResponse = MockedDeclareArrivalResponse::getMockedArrivalSuccessResponse();

        $this->defaultHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$mockedClient->getAccessToken(),
        );
    }

    /**
     * @group create
     * @group revoke-create
     * Test create new revoke declaration
     */
    public function testCreateRevokeDeclaration()
    {
        $message = self::$mockedSuccessResponse;
        $messageNumber = $message->getMessageNumber();

        //Create declare arrival
        $revokeJsonFormat = new RevokeDeclarationJsonFormat();
        $revokeJsonFormat->setMessageNumber($messageNumber);

        //Create json to be posted
        $revokeJson = self::$serializer->serializeToJSON($revokeJsonFormat);

        $this->client->request('POST',
            $this::REVOKE_DECLARATION_ENDPOINT,
            array(),
            array(),
            $this->defaultHeaders,
            $revokeJson
        );

        $response = $this->client->getResponse()->getContent();
        $data = json_decode($response, true);

        $ubn = $message->getDeclareArrivalRequestMessage()->getUbn();
        $this->assertEquals($messageNumber, $data['message_number']);
        $this->assertEquals($message->getRequestId(), $data['request_id']);
        $this->assertEquals($message->getMessageId(), $data['message_id']);
        $this->assertEquals($ubn, $data['ubn']);
    }

    public function tearDown() {
        parent::tearDown();
    }

    /*
     * Runs after all testcases ran and teardown
     */
    public static function tearDownAfterClass()
    {

    }
}