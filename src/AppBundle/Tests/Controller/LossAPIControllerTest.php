<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\DeclareLoss;
use AppBundle\Service\IRSerializer;
use AppBundle\DataFixtures\ORM\MockedAnimal;
use AppBundle\DataFixtures\ORM\MockedClient;
use AppBundle\DataFixtures\ORM\MockedTags;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class LossAPIControllerTest
 * @package AppBundle\Tests\Controller
 * @group loss
 */
class LossAPIControllerTest extends WebTestCase {

  const DECLARE_LOSS_ENDPOINT = "/api/v1/losses";

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
   * @var ArrayCollection
   */
  static private $mockedTagsList;

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

    ///Get mocked tags
    self::$mockedTagsList = MockedTags::getMockedTags();

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
   * @group get
   * @group loss-get 
   * Test retrieving Declare losses list
   */
  public function testGetLosses()
  {
    $this->client->request('GET',
      $this::DECLARE_LOSS_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertEquals(0, sizeof($data['result']));
  }

  /**
   * @group get
   * @group loss-get
   * Test retrieving Declare loss by id
   */
  public function testGetLossById()
  {
    $this->client->request('GET',
      $this::DECLARE_LOSS_ENDPOINT . '/1',
      array(),
      array(),
      $this->defaultHeaders
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertEquals(null, $data);
  }

  /**
   * @group create
   * @group loss-create
   * Test create new Declare loss
   */
  public function testCreateLoss()
  {
    //Create declare loss
    $declareLoss = new DeclareLoss();
    $declareLoss->setReasonOfLoss("Life");
    $declareLoss->setUbnProcessor("2299077");
    $declareLoss->setDateOfDeath(new \DateTime("2024-02-24"));

    $declareLoss->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareLossJson = self::$serializer->serializeToJSON($declareLoss);

    $this->client->request('POST',
      $this::DECLARE_LOSS_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareLossJson
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertEquals('open', $data['request_state']);
    $this->assertEquals($declareLoss->getReasonOfLoss(), $data['reason_of_loss']);
    $this->assertEquals($declareLoss->getUbnProcessor(), $data['ubn_processor']);
  }

  /**
   * @group update
   * @group loss-update
   * Test create new Declare loss
   */
  public function testUpdateLoss()
  {
    //Create declare loss
    $declareLoss = new DeclareLoss();
    $declareLoss->setReasonOfLoss("Accident");
    $declareLoss->setUbnProcessor("666");
    $declareLoss->setDateOfDeath(new \DateTime("2018-05-24"));

    $declareLoss->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareLossJson = self::$serializer->serializeToJSON($declareLoss);

    //Do POST declare loss
    $this->client->request('POST',
      $this::DECLARE_LOSS_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareLossJson
    );

    //Get response
    $response = $this->client->getResponse()->getContent();
    $declareLossResponse = new ArrayCollection(json_decode($response, true));

    //Get requestId so we can do an update with PUT
    $requestId = $declareLossResponse['request_id'];

    //Update value
    $declareLossUpdated = $declareLoss;
    $declareLossUpdated->setReasonOfLoss("Destiny");
    $declareLossUpdated->setUbnProcessor("1");
    $declareLossUpdated->getAnimal()->setUlnNumber('8795441');

    //Create json to be putted
    $declareLossUpdatedJson = self::$serializer->serializeToJSON($declareLossUpdated);

    //PUT updated declare loss
    $this->client->request('PUT',
      $this::DECLARE_LOSS_ENDPOINT . '/'. $requestId,
      array(),
      array(),
      $this->defaultHeaders,
      $declareLossUpdatedJson
    );

    $updatedResponse = $this->client->getResponse()->getContent();
    $updatedData = json_decode($updatedResponse, true);

    //Verify the updated parameters
    $this->assertEquals($declareLossUpdated->getReasonOfLoss(), $updatedData['reason_of_loss']);
    $this->assertEquals($declareLossUpdated->getUbnProcessor(), $updatedData['ubn_processor']);
    $this->assertEquals($declareLossUpdated->getAnimal()->getUlnNumber(), $updatedData['animal']['uln_number']);

    //Verify some unchanged parameters
    $this->assertEquals($declareLossUpdated->getAnimal()->getUlnCountryCode(), $updatedData['animal']['uln_country_code']);
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