<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\DeclareDepart;
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

/**
 * Class DepartAPIControllerTest
 * @package AppBundle\Tests\Controller
 * @group depart
 */
class DepartAPIControllerTest extends WebTestCase {

  const DECLARE_DEPART_ENDPOINT = "/api/v1/departs";

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
   * Test retrieving Declare departures list
   */
  public function testGetDepartures()
  {
    $this->client->request('GET',
      $this::DECLARE_DEPART_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders
    );

    $contentJson = $this->client->getResponse()->getContent();
    $dataArray = json_decode($contentJson, true);

    $this->assertEquals(0, sizeof($dataArray['result']));
  }

  /**
   * Test retrieving Declare departures by id
   */
  public function testGetDeparturesById()
  {
    $this->client->request('GET',
      $this::DECLARE_DEPART_ENDPOINT . '/1',
      array(),
      array(),
      $this->defaultHeaders
    );

    $contentJson = $this->client->getResponse()->getContent();
    $dataArray = json_decode($contentJson, true);

    $this->assertEquals(0, sizeof($dataArray['result']));
  }

  /**
   *
   * Test create new Declare depart
   */
  public function testCreateDepart()
  {
    //Create declare depart
    $declareDepart = new DeclareDepart();
    $declareDepart->setDepartDate(new \DateTime());
    $declareDepart->setUbn("987789");
    $declareDepart->setUbnNewOwner("654456");
    $declareDepart->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareDepartJson = self::$serializer->serializeToJSON($declareDepart);

    $this->client->request('POST',
      $this::DECLARE_DEPART_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareDepartJson
    );

    $contentJson = $this->client->getResponse()->getContent();
    $dataArray = json_decode($contentJson, true);

    $this->assertEquals('open', $dataArray['request_state']);
  }

  /**
   *
   * Test create new Declare Depart
   */
  public function testUpdateDepart()
  {
    //Create declare depart
    $declareDepart = new DeclareDepart();
    $declareDepart->setDepartDate(new \DateTime());
    $declareDepart->setUbn("321111");
    $declareDepart->setUbnNewOwner("123333");
    $declareDepart->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareDepartJson = self::$serializer->serializeToJSON($declareDepart);

    //Do POST declare depart
    $this->client->request('POST',
      $this::DECLARE_DEPART_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareDepartJson
    );

    //Get response
    $contentJson = $this->client->getResponse()->getContent();
    $declareDepartResponseArray = new ArrayCollection(json_decode($contentJson, true));

    //Get requestId so we can do an update with PUT
    $requestId = $declareDepartResponseArray['request_id'];

    //Update value
    $declareDepartUpdated = $declareDepart;
    $declareDepartUpdated->setUbnNewOwner("444441");
    $declareDepartUpdated->getAnimal()->setUlnNumber('555666');

    //Create json to be putted
    $declareDepartUpdatedJson = self::$serializer->serializeToJSON($declareDepartUpdated);

    //PUT updated declare depart
    $this->client->request('PUT',
      $this::DECLARE_DEPART_ENDPOINT . '/'. $requestId,
      array(),
      array(),
      $this->defaultHeaders,
      $declareDepartUpdatedJson
    );

    $updatedResponseJson = $this->client->getResponse()->getContent();
    $updatedDataArray = json_decode($updatedResponseJson, true);

    $this->assertEquals($declareDepartUpdated->getUbnNewOwner(), $updatedDataArray['ubn_new_owner']);
    $this->assertEquals($declareDepart->getUbn(), $updatedDataArray['ubn']);
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