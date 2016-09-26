<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\Location;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

/**
 * Class TagTransferTest
 * @package AppBundle\Tests\Controller
 * @group tag-transfer
 */
class TagTransferTest extends WebTestCase {

  /** @var RequestClient */
  private $client;

  /** @var string */
  static private $accessTokenCode;

  /** @var Location */
  static private $location;

  /** @var IRSerializer */
  static private $serializer;

  /** @var ObjectManager */
  static private $em;

  /** @var array */
  private $defaultHeaders;


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
    self::$em = $container->get('doctrine')->getManager();

    //Database safety check
    $isLocalTestDatabase = Validator::isLocalTestDatabase(self::$em);
    if(!$isLocalTestDatabase) {
      dump(TestConstant::TEST_DB_ERROR_MESSAGE);die;
    }

    self::$location = DoctrineUtil::getRandomActiveLocation(self::$em);
    self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
  }


  /**
   * Runs on each testcase
   */
  public function setUp()
  {
    $this->client = parent::createClient();

    $this->defaultHeaders = array(
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCESSTOKEN' => self::$accessTokenCode,
    );
  }


  /**
   * @group get
   * @group tag-transfer-get
   * Test tag-transfer getter endpoints
   */
  public function testTagTransfersGetters()
  {
    //Get tags-transfers
    $this->client->request('GET',
      Endpoint::DECLARE_TAGS_TRANSFERS_ENDPOINT.'-history',
      array(), array(), $this->defaultHeaders
    );
    $this->assertStatusCode(200, $this->client);

    $this->client->request('GET',
        Endpoint::DECLARE_TAGS_TRANSFERS_ENDPOINT.'-errors',
        array(), array(), $this->defaultHeaders
    );
    $this->assertStatusCode(200, $this->client);
  }


  /**
   * @group post
   * @group tag-transfer-post
   * Test tag-transfer post endpoint
   */
  public function testTagTransferPost()
  {
    $tag = DoctrineUtil::getRandomUnassignedTag(self::$em, self::$location);
    $locationReceiver = DoctrineUtil::getRandomActiveLocation(self::$em, self::$location);
    $relationNumberAcceptant = $locationReceiver->getCompany()->getOwner()->getRelationNumberKeeper();
    $ubnNewOwner = $locationReceiver->getUbn();

    $declareMateJson =
        json_encode(
            [
                "relation_number_acceptant" => $relationNumberAcceptant,
                "ubn_new_owner" => $ubnNewOwner,
                "tags" =>
                [
                    [
                        "uln_country_code" => $tag->getUlnCountryCode(),
                        "uln_number" => $tag->getUlnNumber()
                    ]
                ]
            ]);

    $this->client->request('POST',
        Endpoint::DECLARE_TAGS_TRANSFERS_ENDPOINT,
        array(),
        array(),
        $this->defaultHeaders,
        $declareMateJson
    );

    $response = $this->client->getResponse();
    $data = json_decode($response->getContent(), true);
    $this->assertStatusCode(200, $this->client);
  }



  /**
   * Runs after each testcase
   */
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