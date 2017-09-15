<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\Tag;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportTest
 * @package AppBundle\Tests\Controller
 * @group import
 */
class ImportTest extends WebTestCase
{

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
    /** @var Tag */
    static private $tag;
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
        self::$tag = UnitTestData::createTag(self::$em, self::$location);
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
    }

    public static function tearDownAfterClass()
    {
        UnitTestData::deleteTestAnimals(self::$em->getConnection(), DeclareImport::getTableName());
        UnitTestData::deleteTestTags(self::$em->getConnection());
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
     * @group import-post
     * Test import post endpoint
     */
    public function testImportFromEUCountryPost()
    {
        $countryOfOriginCode = 'FR';

        $declareMateJson =
            json_encode(
                [
                    "is_import_animal" => true,
                    "country_origin" => $countryOfOriginCode,
                    "arrival_date" => "2016-07-31T18:25:43-05:00",
                    "animal" => [
                        "uln_country_code" => $countryOfOriginCode,
                        "uln_number" => self::$tag->getUlnNumber()
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