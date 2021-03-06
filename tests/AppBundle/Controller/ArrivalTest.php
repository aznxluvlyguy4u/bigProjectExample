<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ArrivalTest
 * @package AppBundle\Tests\Controller
 * @group arrival
 * @group rvo
 */
class ArrivalTest extends WebTestCase
{

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
    /** @var Ram */
    static private $ram;
    /** @var IRSerializer */
    static private $serializer;
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
        self::$serializer = $container->get('app.serializer.ir');
        self::$em = $container->get('doctrine')->getManager();

        //Database safety check
        Validator::isTestDatabase(self::$em);

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
        self::$ram = UnitTestData::createTestRam(self::$em, null); //For this test Animal.location = null
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
    }

    public static function tearDownAfterClass()
    {
        UnitTestData::deleteTestAnimals(self::$em->getConnection(),
            [DeclareArrival::getTableName(), DeclareDepart::getTableName()]);
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
     * @group arrival-get
     * @group import
     * Test arrival getter endpoints
     */
    public function testArrivalsGetters()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_ARRIVAL_ENDPOINT . '-history',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_ARRIVAL_ENDPOINT . '-errors',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }

    /**
     * @group post
     * @group arrival-post
     * Test arrival post endpoint
     */
    public function testArrivalPost()
    {
        $otherLocation = UnitTestData::getRandomActiveLocation(self::$em, self::$location);

        $declareMateJson =
            json_encode(
                [
                    "is_import_animal" => false,
                    "ubn_previous_owner" => $otherLocation->getUbn(),
                    "arrival_date" => "2016-07-31T18:25:43-05:00",
                    "animal" => [
                        "uln_country_code" => self::$ram->getUlnCountryCode(),
                        "uln_number" => self::$ram->getUlnNumber()
                    ]
                ]);

        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_ARRIVAL_ENDPOINT,
            array(),
            array(),
            $this->defaultHeaders,
            $declareMateJson
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