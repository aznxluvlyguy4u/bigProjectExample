<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Entity\DeclareLoss;
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
 * Class LossTest
 * @package AppBundle\Tests\Controller
 * @group losses
 */
class LossTest extends WebTestCase
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
        UnitTestData::deleteTestAnimals(self::$em->getConnection(), DeclareLoss::getTableName());
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
     * @group losses-get
     * Test losses getter endpoints
     */
    public function testLosssGetters()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_LOSSES_ENDPOINT . '-history',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_LOSSES_ENDPOINT . '-errors',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }

    /**
     * @group post
     * @group loss-post
     * Test loss post endpoint
     */
    public function testLossPost()
    {
        $ubnDestructor = "2486574";
        $reasonOfLoss = "NO REASON";

        $declareMateJson =
            json_encode(
                [
                    "animal" => [
                        "uln_country_code" => self::$ram->getUlnCountryCode(),
                        "uln_number" => self::$ram->getUlnNumber()
                    ],
                    "date_of_death" => "2016-09-13T00:00:00+0200",
                    "reason_of_loss" => $reasonOfLoss,
                    "ubn_destructor" => $ubnDestructor
                ]);

        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_LOSSES_ENDPOINT,
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