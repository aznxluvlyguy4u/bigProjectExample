<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Location;
use AppBundle\Entity\TreatmentType;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreatmentTemplateTest
 *
 * @package AppBundle\Tests\Controller
 * @group treatment
 * @group treatment-template
 */
class TreatmentTemplateTest extends WebTestCase
{
    const GET_individualDefault = 'GET_individualDefault';
    const GET_individual = 'GET_individual';
    const GET_locationDefault = 'GET_locationDefault';
    const GET_location = 'GET_location';
    const POST_individual = 'POST_individual';
    const POST_location = 'POST_location';
    const EDIT_individual = 'EDIT_individual';
    const EDIT_location = 'EDIT_location';
    const DELETE_individual = 'DELETE_individual';
    const DELETE_location = 'DELETE_location';

    private $endpointSuffixes = [
        self::GET_individualDefault => '/template/individual',
        self::GET_individual => '/template/individual/',//{ubn}
        self::GET_locationDefault => '/template/location',
        self::GET_location => '/template/location/',//{ubn}
        self::POST_individual => '/individual/template',
        self::POST_location => '/location/template',
        self::EDIT_individual => '/individual/template/',//{templateId}
        self::EDIT_location => '/location/template/',//{templateId}
        self::DELETE_individual => '/individual/template/',//{templateId}
        self::DELETE_location => '/location/template/',//{templateId}
    ];

    /** @var string */
    static private $accessTokenCode;
    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var Location */
    static private $location;
    /** @var Faker\Factory */
    static private $faker;
    /** @var TreatmentType */
    static private $treatmentType;
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
        self::$faker = Faker\Factory::create();

        //Database safety check
        Validator::isTestDatabase(self::$em);

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
        self::$accessTokenCode = UnitTestData::getRandomAdminAccessTokenCode(self::$em,AccessLevelType::SUPER_ADMIN);
    }

    public static function tearDownAfterClass()
    {
        if (self::$treatmentType) {
            self::$em->remove(self::$treatmentType);
            self::$em->flush();
        }
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
     * @group treatment-template-get
     */
    public function testGet()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::TREATMENTS . $this->endpointSuffixes[self::GET_individualDefault],
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::TREATMENTS . $this->endpointSuffixes[self::GET_individual] . self::$location->getUbn(),
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::TREATMENTS . $this->endpointSuffixes[self::GET_locationDefault],
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::TREATMENTS . $this->endpointSuffixes[self::GET_location] . self::$location->getUbn(),
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }

    /**
     * @group cud
     * @group treatment-template-type-cud
     */
    public function testCreateUpdateDelete()
    {
        //TODO
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