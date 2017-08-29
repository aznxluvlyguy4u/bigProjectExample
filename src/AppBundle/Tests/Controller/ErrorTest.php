<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\TreatmentType;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Faker;

/**
 * Class ErrorTest
 *
 * @package AppBundle\Tests\Controller
 * @group error
 */
class ErrorTest extends WebTestCase
{
    const GET = 'GET';
    const GET_errorDetails = 'GET_errorDetails';
    const GET_errorDetailsNonIr = 'GET_errorDetailsNonIr';
    const EDIT_ir = 'EDIT_ir'; //old version
    const EDIT_nonIr = 'EDIT_nonIr'; //old version
    const EDIT = 'EDIT';

    private $endpointSuffixes = [
        self::GET => '',
        self::GET_errorDetails => '/',//{messageId}
        self::GET_errorDetailsNonIr => '/non-ir/',//{messageId}
        self::EDIT_ir => '',
        self::EDIT_nonIr => '-nsfo/',//{messageId}
        self::EDIT => '-hidden-status',
    ];

    /** @var string */
    static private $accessTokenCode;
    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
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
        $isLocalTestDatabase = Validator::isLocalTestDatabase(self::$em);
        if (!$isLocalTestDatabase) {
            dump(TestConstant::TEST_DB_ERROR_MESSAGE);
            die;
        }

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
     * @group error-get
     */
    public function testGet()
    {
        //Get tags-transfers
        $this->client->request('GET',
            Endpoint::ERROR_ENDPOINT . $this->endpointSuffixes[self::GET],
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        //TODO
    }

    /**
     * @group cud
     * @group error-cud
     */
    public function testUpdate()
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