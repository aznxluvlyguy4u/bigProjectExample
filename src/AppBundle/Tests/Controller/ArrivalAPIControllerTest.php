<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\MockedTags;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Service\IRSerializer;
use AppBundle\DataFixtures\ORM\MockedAnimal;
use AppBundle\DataFixtures\ORM\MockedClient;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use Doctrine\Common\Collections\ArrayCollection;

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
    $fixtures = array(
      'AppBundle\DataFixtures\ORM\MockedClient',
      'AppBundle\DataFixtures\ORM\MockedAnimal',
      'AppBundle\DataFixtures\ORM\MockedTags');
    $this->loadFixtures($fixtures);

    //Get mocked Client
    self::$mockedClient = MockedClient::getMockedClient();
    $this->accessToken = self::$mockedClient->getAccessToken();

    //Get mocked Animals
    self::$mockedChild  = MockedAnimal::getMockedRamWithParents();
    self::$mockedFather = MockedAnimal::getMockedParentRam();
    self::$mockedMother = MockedAnimal::getMockedParentEwe();

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
  public function testGetArrivals()
  {
    $this->client->request('GET',
      $this::DECLARE_ARRIVAL_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertEquals(0, sizeof($data['result']));
  }

  /**
   * Test retrieving Declare arrival by id
   */
  public function testGetArrivalById()
  {
    $this->client->request('GET',
      $this::DECLARE_ARRIVAL_ENDPOINT . '/1',
      array(),
      array(),
      $this->defaultHeaders
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertEquals(null, $data);
  }

  /**
   *
   * Test create new Declare arrival
   */
  public function testCreateArrival()
  {
    //Create declare arrival
    $declareArrival = new DeclareArrival();
    $declareArrival->setArrivalDate(new \DateTime());
    $declareArrival->setUbnPreviousOwner("123456");
    $declareArrival->setIsImportAnimal(true);
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

    $this->assertEquals('open', $data['request_state']);
  }

  /**
   *
   * Test create new Declare arrival
   */
  public function testUpdateArrival()
  {
    //Create declare arrival
    $declareArrival = new DeclareArrival();
    $declareArrival->setArrivalDate(new \DateTime());
    $declareArrival->setUbnPreviousOwner("123456");
    $declareArrival->setIsImportAnimal(true);
    $declareArrival->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareArrivalJson = self::$serializer->serializeToJSON($declareArrival);

    //Do POST declare arrival
    $this->client->request('POST',
      $this::DECLARE_ARRIVAL_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareArrivalJson
    );

    //Get response
    $response = $this->client->getResponse()->getContent();
    $declareArrivalResponse = new ArrayCollection(json_decode($response, true));

    //Get requestId so we can do an update with PUT
    $requestId = $declareArrivalResponse['request_id'];

    //Update value
    $declareArrivalUpdated = $declareArrival;
    $declareArrivalUpdated->setUbnPreviousOwner("999991");

    //Create json to be putted
    $declareArrivalUpdatedJson = self::$serializer->serializeToJSON($declareArrivalUpdated);

    //PUT updated declare arrival
    $this->client->request('PUT',
      $this::DECLARE_ARRIVAL_ENDPOINT . '/'. $requestId,
      array(),
      array(),
      $this->defaultHeaders,
      $declareArrivalUpdatedJson
    );

    $updatedResponse = $this->client->getResponse()->getContent();
    $updatedData = json_decode($updatedResponse, true);

    $this->assertEquals($declareArrivalUpdated->getUbnPreviousOwner(), $updatedData['ubn_previous_owner']);
    $this->assertEquals($declareArrival->getIsImportAnimal(), $updatedData['is_import_animal']);
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