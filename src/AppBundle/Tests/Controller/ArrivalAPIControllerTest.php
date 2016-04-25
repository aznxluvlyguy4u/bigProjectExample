<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\DeclareArrival;
use AppBundle\Service\IRSerializer;
use AppBundle\DataFixtures\ORM\MockedAnimal;
use AppBundle\DataFixtures\ORM\MockedClient;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Company;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use AppBundle\Enumerator\AnimalType;

class ArrivalAPIControllerTest extends WebTestCase {

  const DECLARE_ARRIVAL_ENDPOINT = "/api/v1/arrivals";

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
   * @var Client
   */
  static private $mockedClient;

  /**
   * @var Ram
   */
  static private $mockedChild;

  /**
   * @var Ram
   */
  static private $mockedFather;

  /**
   * @var Ewe
   */
  static private $mockedMother;

  /**
   * @var array
   */
  private $defaultHeaders;

  /**
   * Runs on each testcase
   */
  public function setUp()
  {
    $this->client = parent::createClient();

    //Load fixture class
    $fixtures = array('AppBundle\DataFixtures\ORM\MockedClient',
                      'AppBundle\DataFixtures\ORM\MockedAnimal');
    $this->loadFixtures($fixtures);

    //Get mocked Client
    self::$mockedClient = MockedClient::$mockedClient;
    $this->accessToken = self::$mockedClient->getAccessToken();

    //Get mocked Animals
    self::$mockedChild = MockedAnimal::$mockedRamWithParents;
    self::$mockedFather = MockedAnimal::$mockedParentRam;
    self::$mockedMother = MockedAnimal::$mockedParentEwe;

    $this->defaultHeaders = array(
      'CONTENT_TYPE' => 'application/json',
      'HTTP_ACCESSTOKEN' => self::$mockedClient->getAccessToken(),
    );
  }

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
    self::$entityManager = $container->get('doctrine.orm.entity_manager');
  }

  /**
   * Test retrieving Declare arrivals list
   */
  public function testGetDeclareArrivals()
  {
    $this->client->request('GET',
                           $this::DECLARE_ARRIVAL_ENDPOINT . '/status/',
                           array(),
                           array(),
                           $this->defaultHeaders
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertSame(0, sizeof($data['result']));
  }

  /**
   * Test retrieving Declare arrival by id
   */
  public function testGetDeclareArrivalById()
  {
    $this->client->request('GET',
                           $this::DECLARE_ARRIVAL_ENDPOINT . '/1',
                           array(),
                           array(),
                           $this->defaultHeaders
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertSame(null, $data);
  }

  /**
   *
   * Test create new Declare arrival
   */
  public function testPostDeclareArrival()
  {
    //Create declare arrival
    $declareArrival = new DeclareArrival();
    $declareArrival->setArrivalDate(new \DateTime());
    $declareArrival->setUbnPreviousOwner("123456");
    $declareArrival->setImportAnimal(true);
    $declareArrival->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareArrivalJson = self::$serializer->serializeToJSON($declareArrival);

    $this->client->request('POST',
                           $this::DECLARE_ARRIVAL_ENDPOINT,
                           array(),
                           array(),
                           $this->defaultHeaders,
                           $declareArrivalJson
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertSame('open', $data['request_state']);
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