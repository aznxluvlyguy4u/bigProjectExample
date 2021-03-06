<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\Client;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

/**
 * Class CountryAPIControllerTest
 * @package AppBundle\Tests\Controller
 * @group country
 */
class CountryAPIControllerTest extends WebTestCase {

  const COUNTRIES_ENDPOINT = "/api/v1/countries";

  /**
   * @var RequestClient
   */
  private $client;

  /**
   * @var ObjectManager
   */
  static private $entityManager;

  /**
   * @var Client
   */
  static private $mockedClient;

  /**
   * @var array
   */
  static private $mockedCountries;

  /**
   * @var array
   */
  private $defaultHeaders;

//  /**
//   * Runs on each testcase
//   */
//  public function setUp()
//  {
//    $this->client = parent::createClient();
//
//    //Load fixture class
//    $fixtures = array('AppBundle\DataFixtures\ORM\MockedClient',
//      'AppBundle\DataFixtures\ORM\MockedCountries');
//    $this->loadFixtures($fixtures);
//
//    //Get mocked Client
//    self::$mockedClient = MockedClient::getMockedClient();
//    $this->accessToken = self::$mockedClient->getAccessToken();
//
//    //Get mocked Country
//    self::$mockedCountries  = MockedCountries::getMockedCountriesList();
//
//    $this->defaultHeaders = array(
//      'CONTENT_TYPE' => 'application/json',
//      'HTTP_ACCESSTOKEN' => self::$mockedClient->getAccessToken(),
//    );
//  }
//
//  /**
//   * Runs before class setup
//   */
//  public static function setUpBeforeClass()
//  {
//    //start the symfony kernel
//    $kernel = static::createKernel();
//    $kernel->boot();
//
//    static::$kernel = static::createKernel();
//    static::$kernel->boot();
//
//    //Get the DI container
//    $container = $kernel->getContainer();
//
//    //Get service classes
//    self::$entityManager = $container->get('doctrine')->getManager();
//  }
//
//  /**
//   * @group get
//   * @group country-get
//   * Test retrieving Countries list
//   */
//  public function testGetCountries()
//  {
//    $this->client->request('GET',
//      $this::COUNTRIES_ENDPOINT,
//      array(),
//      array(),
//      $this->defaultHeaders
//    );
//
//    $response = $this->client->getResponse();
//    $data = json_decode($response->getContent(), true);
//
//    $this->assertEquals(sizeof(self::$mockedCountries), sizeof($data['result']));
//  }
//
//  public function tearDown()
//  {
//    parent::tearDown();
//  }
//
//  /*
//   * Runs after all testcases ran and teardown
//   */
//  public static function tearDownAfterClass()
//  {
//
//  }
}