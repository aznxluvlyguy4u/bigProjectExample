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
 * Class DepartTest
 * @package AppBundle\Tests\Controller
 * @group depart
 * @group rvo
 */
class DepartTest extends WebTestCase
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
        self::$ram = UnitTestData::createTestRam(self::$em, self::$location);
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
     * @group depart-get
     * @group export
     * Test depart getter endpoints
     */
    public function testDepartsGetters()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_DEPART_ENDPOINT . '-history',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_DEPART_ENDPOINT . '-errors',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }

    /**
     * @group post
     * @group depart-post
     * Test depart post endpoint
     */
    public function testDepartPost()
    {
        $otherLocation = UnitTestData::getRandomActiveLocation(self::$em, self::$location);
        $reasonOfDepart = "a very good reason";

        $declareMateJson =
            json_encode(
                [
                    "reason_of_depart" => $reasonOfDepart,
                    "ubn_new_owner" => $otherLocation->getUbn(),
                    "is_export_animal" => false,
                    "animal" => [
                        "uln_country_code" => self::$ram->getUlnCountryCode(),
                        "uln_number" => self::$ram->getUlnNumber()
                    ],
                    "depart_date" => "2012-04-21T18:25:43-05:00"
                ]);

        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_DEPART_ENDPOINT,
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