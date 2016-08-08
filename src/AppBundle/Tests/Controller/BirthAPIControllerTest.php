<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Constant;
use AppBundle\DataFixtures\ORM\MockedTags;
use AppBundle\Entity\DeclareBirth;
use AppBundle\JsonFormat\DeclareBirthJsonFormat;
use AppBundle\Entity\Tag;
use AppBundle\JsonFormat\DeclareBirthJsonFormatChild;
use AppBundle\JsonFormat\DeclareBirthJsonFormatEwe;
use AppBundle\JsonFormat\DeclareBirthJsonFormatRam;
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
use Symfony\Component\Validator\Constraints\DateTime;

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
   * @var ArrayCollection
   */
  static private $mockedTagsList;

  /**
   * @var Client
   */
  static private $mockedClient;

  /**
   * @var Ewe
   */
  static private $mockedSurrogate;

  /**
   * @var Ram
   */
  static private $mockedFather;

  /**
   * @var Ewe
   */
  static private $mockedMother;

  /**
   * @var Ram
   */
  static private $mockedNewBornRam;

  /**
   * @var Ewe
   */
  static private $mockedNewBornEwe;

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
    self::$mockedNewBornRam = MockedAnimal::getMockedNewBornRam();
    self::$mockedNewBornEwe = MockedAnimal::getMockedNewBornEwe();
    self::$mockedFather = MockedAnimal::getMockedParentRam();
    self::$mockedMother = MockedAnimal::getMockedParentEwe();
    self::$mockedSurrogate = MockedAnimal::getMockedAnotherEwe();

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
    //Create parents object in jsonFormat
    $father = new DeclareBirthJsonFormatRam();
    $father->setRamUln(self::$mockedFather);
    $mother = new DeclareBirthJsonFormatEwe();
    $mother->setEweUln(self::$mockedMother);

    //Create child object in jsonFormat with surrogate
    $childRam = new DeclareBirthJsonFormatChild();
    $childRam->setChildValues(self::$mockedNewBornRam);
    $childRam->setIsAlive(true);
    $childRam->setBirthType("keizersnee");
    $childRam->setBirthWeight(231.2);
    $childRam->setBirthTailLength(12.1);
    $childRam->setHasLambar(true);
    $childRam->setSurrogateValues(self::$mockedSurrogate);

    //Create child object in jsonFormat with surrogate
    $childEwe = new DeclareBirthJsonFormatChild();
    $childEwe->setChildValues(self::$mockedNewBornEwe);
    $childEwe->setIsAlive(true);
    $childEwe->setBirthType("keizersnee");
    $childEwe->setBirthWeight(187.3);
    $childEwe->setBirthTailLength(10.5);
    $childEwe->setHasLambar(true);
    

    //Create declare birth object in jsonFormat
    $declareBirthJsonFormat = new DeclareBirthJsonFormat();
    $declareBirthJsonFormat->setBirthType("keizersnee");
    $declareBirthJsonFormat->setIsPseudoPregnancy(false);
    $declareBirthJsonFormat->setIsAborted(false);
    $declareBirthJsonFormat->setDateOfBirth(self::$mockedNewBornRam->getDateOfBirth());
    $declareBirthJsonFormat->setLitterSize(2);
    $declareBirthJsonFormat->setAliveCount(2);

    $declareBirthJsonFormat->setFather($father);
    $declareBirthJsonFormat->setMother($mother);
    $declareBirthJsonFormat->addChild($childRam);
    $declareBirthJsonFormat->addChild($childEwe);
    
    //Create json to be posted
    $declareBirthJson = self::$serializer->serializeToJSON($declareBirthJsonFormat);

    $this->client->request('POST',
      $this::DECLARE_BIRTH_ENDPOINT,
      array(),
      array(),
      $this->defaultHeaders,
      $declareBirthJson
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);
    $responseRam = $data['0'];
    $dataRam = $responseRam['animal'];
    $responseEwe = $data['1'];
    $dataEwe = $responseEwe['animal'];

    //Verify Ram Child
    $this->assertEquals($childRam->getIsAlive(), $dataRam['is_alive'] ? true : false);
    $this->assertEquals($childRam->getUlnCountryCode(), $dataRam['uln_country_code']);
    $this->assertEquals($childRam->getUlnNumber(), $dataRam['uln_number']);
    $this->assertEquals($childRam->getGender(), $dataRam['gender']);

    $this->assertEquals($childRam->getSurrogate()->getUlnCountryCode(), $dataRam[Constant::SURROGATE_NAMESPACE]['uln_country_code']);
    $this->assertEquals($childRam->getSurrogate()->getUlnNumber(), $dataRam[Constant::SURROGATE_NAMESPACE]['uln_number']);

    $this->assertEquals($childRam->getBirthType(), $responseRam['birth_type']);
    $this->assertEquals($declareBirthJsonFormat->getLitterSize(), $responseRam['litter_size']);
    $this->assertEquals($declareBirthJsonFormat->getDateOfBirth(), new \DateTime($responseRam['date_of_birth']));
    $this->assertEquals($declareBirthJsonFormat->getAliveCount(),sizeof($data));
    $this->assertEquals($declareBirthJsonFormat->getIsAborted(),$responseRam['is_aborted']);
    $this->assertEquals($declareBirthJsonFormat->getIsPseudoPregnancy(),$responseRam['is_pseudo_pregnancy']);
    $this->assertEquals($declareBirthJsonFormat->getFather()->getUlnNumber(), $dataRam['parent_father']['uln_number']);
    $this->assertEquals($declareBirthJsonFormat->getMother()->getUlnNumber(), $dataRam['parent_mother']['uln_number']);
    $this->assertEquals($childRam->getBirthWeight(), $responseRam['birth_weight']);
    $this->assertEquals($childRam->getHasLambar(), $responseRam['has_lambar']);
    $this->assertEquals($childRam->getBirthTailLength(), $responseRam['birth_tail_length']);
    
    //Verify Ewe Child
    $this->assertEquals($childEwe->getIsAlive(), $dataEwe['is_alive'] ? true : false);
    $this->assertEquals($childEwe->getUlnCountryCode(), $dataEwe['uln_country_code']);
    $this->assertEquals($childEwe->getUlnNumber(), $dataEwe['uln_number']);
    $this->assertEquals($childEwe->getGender(), $dataEwe['gender']);

    $this->assertEquals($childEwe->getBirthType(), $responseEwe['birth_type']);
    $this->assertEquals($declareBirthJsonFormat->getLitterSize(), $responseEwe['litter_size']);
    $this->assertEquals($declareBirthJsonFormat->getDateOfBirth(), new \DateTime($responseEwe['date_of_birth']));
    $this->assertEquals($declareBirthJsonFormat->getAliveCount(),sizeof($data));
    $this->assertEquals($declareBirthJsonFormat->getIsAborted(),$responseEwe['is_aborted']);
    $this->assertEquals($declareBirthJsonFormat->getIsPseudoPregnancy(),$responseEwe['is_pseudo_pregnancy']);
    $this->assertEquals($declareBirthJsonFormat->getFather()->getUlnNumber(), $dataEwe['parent_father']['uln_number']);
    $this->assertEquals($declareBirthJsonFormat->getMother()->getUlnNumber(), $dataEwe['parent_mother']['uln_number']);
    $this->assertEquals($childEwe->getBirthWeight(), $responseEwe['birth_weight']);
    $this->assertEquals($childEwe->getHasLambar(), $responseEwe['has_lambar']);
    $this->assertEquals($childEwe->getBirthTailLength(), $responseEwe['birth_tail_length']);
  }

  /**
   * @group update
   * @group birth-update
   * Test create new Declare birth
   */
  public function testUpdateBirth()
  {
    //Create parents object in jsonFormat
    $father = new DeclareBirthJsonFormatRam();
    $father->setRamUln(self::$mockedFather);
    $mother = new DeclareBirthJsonFormatEwe();
    $mother->setEweUln(self::$mockedMother);

    //Create child object in jsonFormat with surrogate
    $childEwe = new DeclareBirthJsonFormatChild();
    $childEwe->setChildValues(self::$mockedNewBornEwe);
    $childEwe->setIsAlive("true");
    $childEwe->setBirthType("keizersnee");
    $childEwe->setBirthWeight(187);
    $childEwe->setBirthTailLength(10);
    $childEwe->setHasLambar(false);

    //Create declare birth object in jsonFormat
    $declareBirthJsonFormat = new DeclareBirthJsonFormat();
    $declareBirthJsonFormat->setBirthType("keizersnee");
    $declareBirthJsonFormat->setIsPseudoPregnancy("false");
    $declareBirthJsonFormat->setIsAborted("false");
    $declareBirthJsonFormat->setDateOfBirth(self::$mockedNewBornRam->getDateOfBirth());
    $declareBirthJsonFormat->setLitterSize(1);
    $declareBirthJsonFormat->setAliveCount(1);

    $declareBirthJsonFormat->setFather($father);
    $declareBirthJsonFormat->setMother($mother);
    $declareBirthJsonFormat->addChild($childEwe);

    //Create json to be posted
    $declareBirthJson = self::$serializer->serializeToJSON($declareBirthJsonFormat);

    $this->client->request('POST',
        $this::DECLARE_BIRTH_ENDPOINT,
        array(),
        array(),
        $this->defaultHeaders,
        $declareBirthJson
    );

    //Get response
    $response = $this->client->getResponse();
    $declareBirthResponse = json_decode($response->getContent(), true)['0'];

    //Get requestId so we can do an update with PUT
    $requestId = $declareBirthResponse['request_id'];

    //Update retrieved child from database
    $animalId = $declareBirthResponse['animal']['id'];
    $tag = MockedTags::getOneUnassignedTag();
    $childEweUpdated = self::$entityManager->getRepository(Constant::ANIMAL_REPOSITORY)->find($animalId);
    $childEweUpdated->setAssignedTag($tag);
    $childEweUpdated->setPedigreeCountryCode("DE");
    $childEweUpdated->setPedigreeNumber(682661);


    //Update value, note the format for updating a birth
    //is identical to the structure of the DeclareBirth entity
    $declareBirthUpdated = new DeclareBirth();
    $declareBirthUpdated->setBirthType("Painful but worth it");
    $declareBirthUpdated->setDateOfBirth(new \DateTime('2017-07-07'));
    $declareBirthUpdated->setIsAborted("N");
    $declareBirthUpdated->setIsPseudoPregnancy("N");
    $declareBirthUpdated->setLitterSize(6);
    $declareBirthUpdated->setBirthWeight(842);
    $declareBirthUpdated->setHasLambar(false);
    $declareBirthUpdated->setBirthTailLength(125);
    $declareBirthUpdated->setAnimal($childEweUpdated);

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
    $updatedAnimal = $updatedData['animal'];
    $updatedTag = $updatedAnimal['assigned_tag'];

    //Verify the updated parameters
    $this->assertEquals($declareBirthUpdated->getDateOfBirth(), new \DateTime($updatedData['date_of_birth']));
    $this->assertEquals($declareBirthUpdated->getIsAborted(), $updatedData['is_aborted']);
    $this->assertEquals($declareBirthUpdated->getBirthType(), $updatedData['birth_type']);
    $this->assertEquals($declareBirthUpdated->getIsAborted(), $updatedData['is_aborted']);
    $this->assertEquals($declareBirthUpdated->getBirthWeight(), $updatedData['birth_weight']);
    $this->assertEquals($declareBirthUpdated->getHasLambar(), $updatedData['has_lambar']);
    $this->assertEquals($declareBirthUpdated->getIsPseudoPregnancy(), $updatedData['is_pseudo_pregnancy']);
    $this->assertEquals($declareBirthUpdated->getLitterSize(), $updatedData['litter_size']);
    $this->assertEquals($declareBirthUpdated->getBirthTailLength(), $updatedData['birth_tail_length']);



    if(TestSettings::TestEntitiesAreIdentical){
      //Verify updated variables
      $this->assertEquals($childEweUpdated->getDateOfBirth(), new \DateTime($updatedData['date_of_birth']));
      $this->assertEquals($childEweUpdated->getAssignedTag()->getUlnCountryCode(), $updatedTag['uln_country_code']);
      $this->assertEquals($childEweUpdated->getAssignedTag()->getUlnNumber(), $updatedTag['uln_number']);
      $this->assertEquals($childEweUpdated->getPedigreeCountryCode(), $updatedTag['pedigree_country_code']);
      $this->assertEquals($childEweUpdated->getPedigreeNumber(), $updatedTag['pedigree_number']);

      //Verify if entities are identical
      $this->assertEquals($childEweUpdated->getId(), $updatedAnimal['id']);
      $this->assertEquals($tag->getId(), $updatedTag['id']);
    }
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