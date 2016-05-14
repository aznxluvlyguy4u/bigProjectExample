<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\MockedTags;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Tag;
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
 * Class BirthAPIControllerTest
 * @package AppBundle\Tests\Controller
 * @group birth
 */
class BirthAPIControllerTest extends WebTestCase {

  const DECLARE_BIRTH_ENDPOINT = "/api/v1/births";

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
    self::$mockedChild  = MockedAnimal::getMockedNewBornRam();
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
   * @group get
   * @group birth-get
   * Test retrieving Declare births list
   */
  public function testGetBirths()
  {
    $this->client->request('GET',
      $this::DECLARE_BIRTH_ENDPOINT,
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
   * @group birth-get
   * Test retrieving Declare birth by id
   */
  public function testGetBirthById()
  {
    $this->client->request('GET',
      $this::DECLARE_BIRTH_ENDPOINT . '/1',
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
   * @group birth-create
   * Test create new Declare birth
   */
  public function testCreateBirth()
  {
    //Create declare birth
    $declareBirth = new DeclareBirth();
    $declareBirth->setBirthType("keizersnee");
    $declareBirth->setUbn("777777");

    $declareBirth->setDateOfBirth(self::$mockedChild->getDateOfBirth());

    $unassignedTags = MockedTags::getMockedTags();
    $tag = $unassignedTags->get(1);
    $tag->setTagStatus('assigned');
    $tag->setAnimal(self::$mockedChild);
    self::$mockedChild->setAssignedTag($tag);

    $declareBirth->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareBirthJson = self::$serializer->serializeToJSON($declareBirth);

    $this->client->request('POST',
      $this::DECLARE_BIRTH_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareBirthJson
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);

    $this->assertEquals('open', $data['request_state']);
  }

  /**
   * @group update
   * @group birth-update
   * Test create new Declare birth
   */
  public function testUpdateBirth()
  {
    //Create declare birth
    $declareBirth = new DeclareBirth();
    $declareBirth->setBirthType("keizersnee");
    $declareBirth->setUbn("777777");
    $declareBirth->setDateOfBirth(self::$mockedChild->getDateOfBirth());

    $unassignedTags = MockedTags::getMockedTags();
    $tag = $unassignedTags->get(0);
    $tag->setTagStatus('assigned');
    $tag->setAnimal(self::$mockedChild);
    self::$mockedChild->setAssignedTag($tag);

    $declareBirth->setAnimal(self::$mockedChild);

    //Create json to be posted
    $declareBirthJson = self::$serializer->serializeToJSON($declareBirth);

    //Do POST declare birth
    $this->client->request('POST',
      $this::DECLARE_BIRTH_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareBirthJson
    );

    //Get response
    $response = $this->client->getResponse()->getContent();
    $declareBirthResponse = json_decode($response, true);

    //Get requestId so we can do an update with PUT
    $requestId = $declareBirthResponse['request_id'];

    //Update value
    $declareBirthUpdated = $declareBirth;
    $declareBirthUpdated->setBirthType("Painful but worth it");
    $declareBirthUpdated->setDateOfBirth(new \DateTime());
    $declareBirthUpdated->setAborted("N");
    $declareBirthUpdated->setAnimalWeight(999);
    $declareBirthUpdated->setLambar("N");
    $declareBirthUpdated->setPseudoPregnancy("N");
    $declareBirthUpdated->setLitterSize(6);
    $declareBirthUpdated->setBirthTailLength(1425);
    $declareBirthUpdated->setAnimalWeight(842);

    //Create json to be putted
    $declareBirthUpdatedJson = self::$serializer->serializeToJSON($declareBirthUpdated);

    //PUT updated declare birth
    $this->client->request('PUT',
      $this::DECLARE_BIRTH_ENDPOINT . '/'. $requestId,
      array(),
      array(),
      $this->defaultHeaders,
      $declareBirthUpdatedJson
    );

    $updatedResponse = $this->client->getResponse()->getContent();

    $updatedData = json_decode($updatedResponse, true);
    $tag = $updatedData['animal']['assigned_tag'];

    //Verify the updated parameters

    $this->assertEquals($declareBirthUpdated->getDateOfBirth(), new \DateTime($updatedData['date_of_birth']));
    $this->assertEquals($declareBirthUpdated->getBirthType(), $updatedData['birth_type']);
    $this->assertEquals($declareBirthUpdated->getAborted(), $updatedData['aborted']);
    $this->assertEquals($declareBirthUpdated->getAnimalWeight(), $updatedData['animal_weight']);
    $this->assertEquals($declareBirthUpdated->getLambar(), $updatedData['lambar']);
    $this->assertEquals($declareBirthUpdated->getPseudoPregnancy(), $updatedData['pseudo_pregnancy']);
    $this->assertEquals($declareBirthUpdated->getLitterSize(), $updatedData['litter_size']);
    $this->assertEquals($declareBirthUpdated->getBirthTailLength(), $updatedData['tail_length']);
    $this->assertEquals($declareBirthUpdated->getAnimalWeight(), $updatedData['animal_weight']);
    $this->assertEquals($declareBirthUpdated->getAnimal()->getAssignedTag()->getUlnNumber(), $tag['uln_number']);

    //Verify some unchanged parameters
    $this->assertEquals($declareBirth->getAnimal()->getAssignedTag()->getUlnCountryCode(), $tag['uln_country_code']);
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