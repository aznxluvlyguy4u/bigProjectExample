<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UbnTest
 *
 * @package AppBundle\Tests\Controller
 * @group ubns
 */
class UbnTest extends WebTestCase
{
    const GET_all = 'GET_all';
    const GET_processors = 'GET_processors';
    const POST = 'POST';

    private $endpointSuffixes = [
        self::GET_all => '',
        self::GET_processors => '/processors',
        self::POST => '', //Currently not being tested
    ];

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var string */
    static private $emailAddress;
    /** @var VwaEmployee */
    static private $vwaEmployee;
    /** @var Client */
    static private $nsfoClient;
    /** @var RequestClient */
    private $client;
    /** @var array */
    private $defaultAdminHeaders;
    /** @var array */
    private $vwaEmployeeHeaders;
    /** @var array */
    private $nsfoClientHeaders;


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
        self::$emailAddress = $container->getParameter('test_email');

        //Database safety check
        Validator::isTestDatabase(self::$em);

        self::$accessTokenCode = UnitTestData::getRandomAdminAccessTokenCode(self::$em,AccessLevelType::SUPER_ADMIN);
        self::$vwaEmployee = UnitTestData::getOrCreateVwaEmployee(self::$em, self::$emailAddress);

        $location = UnitTestData::getRandomActiveLocation(self::$em);
        self::$nsfoClient = $location ? $location->getOwner() : null;
    }

    public static function tearDownAfterClass()
    {
        $vwaEmployees = self::$em->getRepository(VwaEmployee::class)->findBy(['emailAddress' => self::$emailAddress]);
        /** @var VwaEmployee $vwaEmployee */
        foreach ($vwaEmployees as $vwaEmployee) {
            $id = $vwaEmployee->getId();

            $sql = "DELETE FROM action_log WHERE action_by_id = $id OR user_account_id = $id";
            self::$em->getConnection()->exec($sql);

            self::$em->remove($vwaEmployee);
            self::$em->flush();
        }
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

        $this->vwaEmployeeHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$vwaEmployee->getAccessToken(),
        );

        $this->nsfoClientHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$nsfoClient->getAccessToken(),
        );
    }

    /**
     * @group get
     * @group ubns-get
     */
    public function testGet()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::UBNS . $this->endpointSuffixes[self::GET_all],
            array(), array(), $this->vwaEmployeeHeaders
        );
        $this->assertStatusCode(200, $this->client);



        $this->client->request(Request::METHOD_GET,
            Endpoint::UBNS . $this->endpointSuffixes[self::GET_processors],
            array(), array(), $this->defaultAdminHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request(Request::METHOD_GET,
            Endpoint::UBNS . $this->endpointSuffixes[self::GET_processors],
            array(), array(), $this->nsfoClientHeaders
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