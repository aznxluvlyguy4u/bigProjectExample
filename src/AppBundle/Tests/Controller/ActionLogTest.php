<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

/**
 * Class ActionLogTest
 *
 * @package AppBundle\Tests\Controller
 * @group log
 * @group action-log
 */
class ActionLogTest extends WebTestCase
{
    const TEST_GETTING_ALL_ACTION_LOGS = false;

    const GET = 'GET';
    const GET_actionAccountOwners = 'GET_actionAccountOwners';
    const GET_actionTypes = 'GET_actionTypes';

    private $endpointSuffixes = [
        self::GET => '',
        self::GET_actionAccountOwners => '-account-owners',
        self::GET_actionTypes => '-types',
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
        $isLocalTestDatabase = Validator::isLocalTestDatabase(self::$em);
        if (!$isLocalTestDatabase) {
            dump(TestConstant::TEST_DB_ERROR_MESSAGE);
            die;
        }

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
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

        $this->defaultHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$accessTokenCode,
        );
    }

    /**
     * @group get
     * @group action-log-get
     */
    public function testGet()
    {
        $this->client->request('GET',
            Endpoint::ACTION_LOG . $this->endpointSuffixes[self::GET]
            .'?start_date=2016-09-10&end_date=2017-09-05&user_action_type=DECLARE_EXPORT&user_account_id=628',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);


        if (self::TEST_GETTING_ALL_ACTION_LOGS) {
            $this->client->request('GET',
                Endpoint::ACTION_LOG . $this->endpointSuffixes[self::GET],
                array(), array(), $this->defaultHeaders
            );
            $this->assertStatusCode(200, $this->client);
        }


        $this->client->request('GET',
            Endpoint::ACTION_LOG . $this->endpointSuffixes[self::GET_actionAccountOwners],
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);


        $this->client->request('GET',
            Endpoint::ACTION_LOG . $this->endpointSuffixes[self::GET_actionTypes]
            .'?user_account_id=628',
            array(), array(), $this->defaultHeaders
        );
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