<?php


namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class ReportTest
 * @group report
 * @package AppBundle\Tests\Controller
 */
class ReportTest extends WebTestCase
{
    /*
     * Test location should have alive males and females
     */
    const TEST_LOCATION_ID = 262;

    const POST_pedigreeCertificates = 'POST_pedigreeCertificates';
    const POST_inbreedingCoefficientReport = 'POST_inbreedingCoefficientReport';
    const POST_livestockReport = 'POST_livestockReport';

    private $endpointSuffixes = [
        self::POST_pedigreeCertificates => '/pedigree-certificates',
        self::POST_inbreedingCoefficientReport => '/inbreeding-coefficients',
        self::POST_livestockReport => '/livestock',
    ];

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
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

        self::$location = self::$em->getRepository(Location::class)->find(self::TEST_LOCATION_ID);
        self::$accessTokenCode = self::$location->getOwner()->getAccessToken();
    }

    public static function tearDownAfterClass()
    {
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
     * @group post
     * @group report-pedigree-certificates
     */
    public function testPedigreeCertificatePost()
    {

        $json =
            json_encode(
                [
                    "animals" => UnitTestData::getAnimalsUlnsBody(self::$em, self::$location, 3),
                ]);

        $this->client->request('POST',
            Endpoint::REPORT . $this->endpointSuffixes[self::POST_pedigreeCertificates],
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group post
     * @group report-inbreedingcoefficient
     */
    public function testInbreedingCoefficientPost()
    {
        $ram = UnitTestData::getAnimalsUlnsBody(self::$em, self::$location, 1, Ram::class);
        $ewes = UnitTestData::getAnimalsUlnsBody(self::$em, self::$location, 3, Ewe::class);

        $json =
            json_encode(
                [
                    "ram" => array_pop($ram),
                    "ewes" => $ewes,
                ]);

        $this->client->request('POST',
            Endpoint::REPORT . $this->endpointSuffixes[self::POST_inbreedingCoefficientReport],
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group post
     * @group report-livestock
     */
    public function testLivestockFullPost()
    {
        $totalAnimalCount = 10;

        $json = null;

        $this->client->request('POST',
            Endpoint::REPORT . $this->endpointSuffixes[self::POST_livestockReport],
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);

        $this->client->request('POST',
            Endpoint::REPORT . $this->endpointSuffixes[self::POST_livestockReport] . '?file_type=csv',
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group post
     * @group report-livestock
     */
    public function testLivestockSelectedUlnPost()
    {
        $totalAnimalCount = 10;

        $json =
            json_encode(
                [
                    "animals" => UnitTestData::getAnimalsUlnsBody(self::$em, self::$location, $totalAnimalCount),
                ]);

        $this->client->request('POST',
            Endpoint::REPORT . $this->endpointSuffixes[self::POST_livestockReport], //as pdf
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);


        $this->client->request('POST',
            Endpoint::REPORT . $this->endpointSuffixes[self::POST_livestockReport] . '?file_type=csv', // as csv
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