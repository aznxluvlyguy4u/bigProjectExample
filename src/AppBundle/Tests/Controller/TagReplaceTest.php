<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
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
 * Class TagReplaceTest
 * @package AppBundle\Tests\Controller
 * @group tag-replace
 */
class TagReplaceTest extends WebTestCase
{

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
    /** @var Ram */
    static private $ram;
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
        self::$ram = UnitTestData::createTestRam(self::$em, self::$location);
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
    }

    public static function tearDownAfterClass()
    {
        UnitTestData::deleteTestAnimals(self::$em->getConnection(), DeclareTagReplace::getTableName());
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
     * @group tag-replace-post
     * Test tag-replace post endpoint
     */
    public function testTagReplacePost()
    {
        $declareMateJson =
            json_encode(
                [
                    "replace_date" => "2016-06-09T19:25:43-05:00",  //if missing, the logData is used for replaceDate
                    "tag" => [
                        "uln_country_code" => self::$tag->getUlnCountryCode(),
                        "uln_number" => self::$tag->getUlnNumber()
                    ],
                    "animal" => [
                        "uln_country_code" => self::$ram->getUlnCountryCode(),
                        "uln_number" => self::$ram->getUlnNumber()
                    ]
                ]);

        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_TAG_REPLACE_ENDPOINT,
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