<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PedigreeRegisterTest
 *
 * @package AppBundle\Tests\Controller
 * @group pedigree-register
 */
class PedigreeRegisterTest extends WebTestCase
{
    const GET_all = 'GET_all';

    private $endpointSuffixes = [
        self::GET_all => '',
    ];

    /** @var string */
    static private $accessTokenCode;
    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var RequestClient */
    private $client;
    /** @var array */
    private $defaultAdminHeaders;


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

        self::$accessTokenCode = UnitTestData::getRandomAdminAccessTokenCode(self::$em,AccessLevelType::SUPER_ADMIN);
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

        $this->defaultAdminHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$accessTokenCode,
        );
    }

    /**
     * @group get
     * @group pedigree-register-get
     */
    public function testGet()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::PEDIGREE_REGISTER . $this->endpointSuffixes[self::GET_all],
            array(), array(), $this->defaultAdminHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::PEDIGREE_REGISTER . $this->endpointSuffixes[self::GET_all] . '?include_non_nsfo_registers=true',
            array(), array(), $this->defaultAdminHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        parent::tearDown();
    }



}