<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Service\IRSerializer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class ArrivalAPIControllerTest extends WebTestCase {

  const DECLARE_ARRRIVAL_ENDPOINT = "/ap/v1/arrivals";

  /**
   * @var Client
   */
  private $client;

  /**
   * @var IRSerializer
   */
  private $serializer;


  /**
   * Runs on each testcase
   */
  public function setUp()
  {
    $this->client = parent::createClient();
    //$this->serializer = $this->get('app.serializer.ir');

  }

  /**
   * Runs before class setup
   */
  public static function setUpBeforeClass()
  {
    //start the symfony kernel
     $kernel = static::createKernel();
     $kernel->boot();

     //get the DI container
     $serializer =  $kernel->getContainer()->get('app.serializer.ir');

     //now we can instantiate our service (if you want a fresh one for
     //each test method, do this in setUp() instead
     //$serializer= self::$container->get('app.serializer.ir');
  }

  /**
   * Test retrieving Declare arrivals list
   */
  public function testGetDeclareArrivals()
  {
    $client = $this->createClient();
    $client->request('GET', $this::DECLARE_ARRRIVAL_ENDPOINT);
    $response = $client->getResponse();

    $data = json_decode($response->getContent(), true);
    $this->assertSame(null, $data['arrivals']);
  }

  /**
   * Test retrieving Declare arrival by id
   */
  public function testGetDeclareArrivalById()
  {
    $client = $this->createClient();
    $client->request('GET', $this::DECLARE_ARRRIVAL_ENDPOINT . '/1');
    $response = $client->getResponse();

    $data = json_decode($response->getContent(), true);
    $this->assertSame(null, $data);
  }

  /**
   * Test create new Declare arrival
   */
  public function testPostDeclareArrival()
  {
    $client = $this->createClient();
    $client->request('POST', $this::DECLARE_ARRRIVAL_ENDPOINT);
    $response = $client->getResponse();

    $data = json_decode($response->getContent(), true);
    $this->assertSame(null, $data);
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