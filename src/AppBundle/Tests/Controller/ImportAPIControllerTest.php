<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\DeclareImport;
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
 * Class ImportAPIControllerTest
 * @package AppBundle\Tests\Controller
 * @group import
 */
class ImportAPIControllerTest extends WebTestCase {


  const DECLARE_IMPORT_ENDPOINT = "/api/v1/imports";

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
   * Test retrieving Declare imports list
   */
  public function testGetImports()
  {
    $this->client->request('GET',
      $this::DECLARE_IMPORT_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertEquals(0, sizeof($data['result']));
  }

  /**
   * Test retrieving Declare import by id
   */
  public function testGetImportById()
  {
    $this->client->request('GET',
      $this::DECLARE_IMPORT_ENDPOINT . '/1',
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
   * Test create new Declare import
   */
  public function testCreateImport()
  {
    //Create declare import
    $declareImport = new DeclareImport();
    $declareImport->setImportDate(new \DateTime());
    $declareImport->setUbnPreviousOwner("123456");
    $declareImport->setImportAnimal(true);
    $declareImport->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareImportJson = self::$serializer->serializeToJSON($declareImport);

    $this->client->request('POST',
      $this::DECLARE_IMPORT_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareImportJson
    );

    $response = $this->client->getResponse();

    $data = json_decode($response->getContent(), true);

    $this->assertEquals('open', $data['request_state']);
  }

  /**
   *
   * Test create new Declare import
   */
  public function testUpdateImport()
  {
    //Create declare import
    $declareImport = new DeclareImport();
    $declareImport->setImportDate(new \DateTime());
    $declareImport->setUbnPreviousOwner("123456");
    $declareImport->setImportAnimal(true);
    $declareImport->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareImportJson = self::$serializer->serializeToJSON($declareImport);

    //Do POST declare import
    $this->client->request('POST',
      $this::DECLARE_IMPORT_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareImportJson
    );

    //Get response
    $response = $this->client->getResponse()->getContent();
    $declareImportResponse = new ArrayCollection(json_decode($response, true));

    //Get requestId so we can do an update with PUT
    $requestId = $declareImportResponse['request_id'];

    //Update value
    $declareImportUpdated = $declareImport;
    $declareImportUpdated->setUbnPreviousOwner("999991");

    //Create json to be putted
    $declareImportUpdatedJson = self::$serializer->serializeToJSON($declareImportUpdated);

    //PUT updated declare import
    $this->client->request('PUT',
      $this::DECLARE_IMPORT_ENDPOINT . '/'. $requestId,
      array(),
      array(),
      $this->defaultHeaders,
      $declareImportUpdatedJson
    );

    $updatedResponse = $this->client->getResponse()->getContent();
    $updatedData = json_decode($updatedResponse, true);

    $this->assertEquals($declareImportUpdated->getUbnPreviousOwner(), $updatedData['ubn_previous_owner']);
    $this->assertEquals($declareImportUpdated->getImportAnimal(), $updatedData['import_animal']);
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