<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AnimalDetailsTest
 * @package AppBundle\Tests\Controller
 * @group animal
 * @group animal-details
 */
class AnimalDetailsTest extends WebTestCase
{
    const GET = 'GET';
    const PUT = 'PUT';

    private $endpointSuffixes = [
        self::GET => '-details/', //{uln}
        self::PUT => '-details/', //{uln}
    ];

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
    /** @var Ram */
    static private $ram;
    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var RequestClient */
    private $client;
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
        self::$em = $container->get('doctrine')->getManager();

        //Database safety check
        Validator::isTestDatabase(self::$em);

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
        self::$ram = UnitTestData::createTestRam(self::$em, self::$location);
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
    }

    public static function tearDownAfterClass()
    {
        UnitTestData::deleteTestAnimals(self::$em->getConnection());
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
     * @group animal-get
     * @group animal-details-get
     */
    public function testGetters()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::GET] . self::$ram->getUln(),
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group put
     * @group animal-put
     * @group animal-details-put
     */
    public function testEdits()
    {
        $json =
            json_encode(
                [
                    "collar" => [
                        "number" => 9182,
                        "color" => 'TEAL'
                    ]
                ]);

        $this->client->request(Request::METHOD_PUT,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::PUT] . self::$ram->getUln(),
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);
    }


    /*
     * Runs after all testcases ran and teardown
     */

    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        parent::tearDown();
    }
}