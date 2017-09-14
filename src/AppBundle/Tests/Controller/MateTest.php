<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MateTest
 * @package AppBundle\Tests\Controller
 * @group matings
 */
class MateTest extends WebTestCase
{

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
    /** @var Ewe */
    static private $ewe;
    /** @var Ram */
    static private $ram;
    /** @var IRSerializer */
    static private $serializer;
    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var Logger */
    static private $logger;
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
        self::$logger = $container->get('logger');

        //Database safety check
        Validator::isTestDatabase(self::$em);

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
        //Ewe should be on location of client, but Ram does not have to be.
        self::$ewe = UnitTestData::createTestEwe(self::$em, self::$location);
        self::$ram = UnitTestData::createTestRam(self::$em);
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
    }


    public static function tearDownAfterClass()
    {
        self::$em->refresh(self::$ewe);
        //$ewe = self::$em->getRepository(Ewe::class)->find(self::$ewe->getId());
        foreach (self::$ewe->getMatings() as $mating) {
            self::$em->remove($mating);
        }

        self::$em->remove(self::$ewe);
        self::$em->remove(self::$ram);
        self::$em->flush();
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
        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_MATINGS_ENDPOINT . '-history',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_MATINGS_ENDPOINT . '-errors',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_MATINGS_ENDPOINT . '-pending',
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
        if (!BirthTest::TEST_BIRTH_CUD) {
            $declareMateJson =
                json_encode(
                    [
                        "start_date" => "2016-07-31T18:25:43-05:00",
                        "end_date" => "2016-07-31T18:25:43-05:00",
                        "ki" => false,
                        "pmsg" => false,
                        "ram" => [
                            "uln_country_code" => self::$ram->getUlnCountryCode(),
                            "uln_number" => self::$ram->getUlnNumber()
                        ],
                        "ewe" => [
                            "uln_country_code" => self::$ewe->getUlnCountryCode(),
                            "uln_number" => self::$ewe->getUlnNumber()
                        ]
                    ]);

            $this->client->request(Request::METHOD_POST,
                Endpoint::DECLARE_MATINGS_ENDPOINT,
                array(),
                array(),
                $this->defaultHeaders,
                $declareMateJson
            );

            $response = $this->client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertStatusCode(200, $this->client);
        }
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