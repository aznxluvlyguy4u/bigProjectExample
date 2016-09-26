<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\TestConstant;
use AppBundle\Entity\Location;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

/**
 * Class MateTest
 * @package AppBundle\Tests\Controller
 * @group matings
 */
class MateTest extends WebTestCase {

  const DECLARE_MATINGS_ENDPOINT = "/api/v1/matings";

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
   * @group matings-get
   * Test matings getter endpoints
   */
  public function testMatingsGetters()
  {
    $this->client->request('GET',
      $this::DECLARE_MATINGS_ENDPOINT.'-history',
      array(), array(), $this->defaultHeaders
    );
    $this->assertStatusCode(200, $this->client);

    $this->client->request('GET',
        $this::DECLARE_MATINGS_ENDPOINT.'-errors',
        array(), array(), $this->defaultHeaders
    );
    $this->assertStatusCode(200, $this->client);

    $this->client->request('GET',
        $this::DECLARE_MATINGS_ENDPOINT.'-pending',
        array(), array(), $this->defaultHeaders
    );
    $this->assertStatusCode(200, $this->client);
  }


  /**
   * @group post
   * @group matings-post
   * Test matings post endpoint
   */
  public function testMatingPost()
  {
    $ram = DoctrineUtil::getRandomRamFromLocation(self::$em, self::$location);
    $ewe = DoctrineUtil::getRandomEweFromLocation(self::$em, self::$location);
    
    $declareMateJson =
        json_encode(
        [
          "start_date" => "2016-07-31T18:25:43-05:00",
            "end_date" => "2016-07-31T18:25:43-05:00",
                  "ki" => false,
                "pmsg" => false,
                  "ram" => [
                              "uln_country_code" => $ram->getUlnCountryCode(),
                              "uln_number" => $ram->getUlnNumber()
                            ],
                  "ewe" => [
                              "uln_country_code" => $ewe->getUlnCountryCode(),
                              "uln_number" => $ewe->getUlnNumber()
                            ]
        ]);

    $this->client->request('POST',
      $this::DECLARE_MATINGS_ENDPOINT,
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