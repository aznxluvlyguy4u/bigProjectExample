<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\MockedTags;
use AppBundle\Entity\DeclareExport;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Service\IRSerializer;
use AppBundle\DataFixtures\ORM\MockedAnimal;
use AppBundle\DataFixtures\ORM\MockedClient;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Proxies\__CG__\AppBundle\Entity\Location;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use Doctrine\Common\Collections\ArrayCollection;

class DeclareExportAPIControllerTest extends  WebTestCase {

  const DECLARE_EXPORT_ENDPOINT = "/api/v1/exports";

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
   * Test retrieving Declare exports list
   */
  public function testGetExports()
  {
    $this->client->request('GET',
      $this::DECLARE_EXPORT_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertEquals(0, sizeof($data['result']));
  }

  /**
   * Test retrieving Declare export by id
   */
  public function testGetExportById()
  {
    $this->client->request('GET',
      $this::DECLARE_EXPORT_ENDPOINT . '/1',
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
   * Test create new Declare export
   */
  public function testCreateExport()
  {
    //Create declare export
    $declareExport = new DeclareExport();
    $declareExport->setExportDate(new \DateTime());
    $declareExport->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareExportJson = self::$serializer->serializeToJSON($declareExport);

    $this->client->request('POST',
      $this::DECLARE_EXPORT_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareExportJson
    );

    $response = $this->client->getResponse();

    $data = json_decode($response->getContent(), true);

    $this->assertEquals(RequestStateType::OPEN, $data['request_state']);
  }

  /**
   *
   * Test create new Declare export
   */
  public function testUpdateExport()
  {
    //Create declare export
    $declareExport = new DeclareExport();
    $declareExport->setExportDate(new \DateTime());
    $declareExport->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareExportJson = self::$serializer->serializeToJSON($declareExport);

    //Do POST declare export
    $this->client->request('POST',
      $this::DECLARE_EXPORT_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareExportJson
    );

    //Get response
    $response = $this->client->getResponse()->getContent();
    $declareExportResponse = json_decode($response, true);

    //Get requestId so we can do an update with PUT
    $requestId = $declareExportResponse['request_id'];

    //Update value
    $declareExportUpdated = $declareExport;

    self::$mockedChild->getAssignedTag()->setUlnCountryCode("NL");
    $updatedDateString = "2000-01-01T16:22:43-0500";
    $declareExportUpdated->setExportDate(new \DateTime($updatedDateString));

    //Create json to be putted
    $declareExportUpdatedJson = self::$serializer->serializeToJSON($declareExportUpdated);

    //PUT updated declare export
    $this->client->request('PUT',
      $this::DECLARE_EXPORT_ENDPOINT . '/'. $requestId,
      array(),
      array(),
      $this->defaultHeaders,
      $declareExportUpdatedJson
    );

    $updatedResponse = $this->client->getResponse()->getContent();
    $updatedData = json_decode($updatedResponse, true);

    $this->assertEquals($updatedDateString, $updatedData['export_date']);
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