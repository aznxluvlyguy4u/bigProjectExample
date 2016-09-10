<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\TestConstant;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

/**
 * Class TagTest
 * @package AppBundle\Tests\Controller
 * @group tag
 */
class TagTest extends WebTestCase {

  const DECLARE_TAG_ENDPOINT = "/api/v1/tags";

  /** @var RequestClient */
  private $client;

  /** @var string */
  private $accessTokenCode;

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
  }


  /**
   * Runs on each testcase
   */
  public function setUp()
  {
    $this->client = parent::createClient();

    $this->accessTokenCode = $this->getContainer()->getParameter('unit_test_access_token');
    $this->defaultHeaders = array(
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCESSTOKEN' => $this->accessTokenCode,
    );
  }


  /**
   * @group get
   * @group tag-get
   * Test tag getter endpoints
   */
  public function testTagsGetters()
  {
    $this->client->request('GET',
      $this::DECLARE_TAG_ENDPOINT,
      array(), array(), $this->defaultHeaders
    );
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