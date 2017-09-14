<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Constant;
use AppBundle\Constant\Endpoint;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Location;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

/**
 * Class VwaEmployeeTest
 *
 * @package AppBundle\Tests\Controller
 * @group vwa
 * @group vwa-employee
 */
class VwaEmployeeTest extends WebTestCase
{
    const GET_all = 'GET_all';
    const GET_byId = 'GET_byId';
    const GET_own = 'GET_own';
    const POST = 'POST';
    const PUT_byId = 'PUT_byId';
    const PUT_own = 'PUT_own';
    const DELETE = 'DELETE';
    const GET_authorize = 'GET_authorize';

    private $endpointSuffixes = [
        self::GET_all => '',
        self::GET_byId => '/', //{id}
        self::GET_own => '/me',
        self::POST => '',
        self::PUT_byId => '/', //{id}
        self::PUT_own => '/me',
        self::DELETE => '/', //{id}
        self::GET_authorize => '/auth/authorize',
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
    /** @var RequestClient */
    private $client;
    /** @var array */
    private $defaultAdminHeaders;
    /** @var array */
    private $vwaEmployeeHeaders;


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

        self::$vwaEmployee = UnitTestData::getOrCreateVwaEmployee(self::$em, self::$emailAddress);

        $this->vwaEmployeeHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$vwaEmployee->getAccessToken(),
        );
    }

    /**
     * @group get
     * @group vwa-employee-get
     */
    public function testGet()
    {
        $this->client->request('GET',
            Endpoint::VWA_EMPLOYEE . $this->endpointSuffixes[self::GET_all],
            array(), array(), $this->defaultAdminHeaders
        );
        $this->assertStatusCode(200, $this->client);


        $this->client->request('GET',
            Endpoint::VWA_EMPLOYEE . $this->endpointSuffixes[self::GET_byId]. self::$vwaEmployee->getPersonId(),
            array(), array(), $this->defaultAdminHeaders
        );
        $this->assertStatusCode(200, $this->client);



        $this->client->request('GET',
            Endpoint::VWA_EMPLOYEE . $this->endpointSuffixes[self::GET_own],
            array(), array(), $this->vwaEmployeeHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group cud
     * @group vwa-employee-cud
     */
    public function testCreateUpdateDeactivate()
    {
        $this->client->request('DELETE',
            Endpoint::VWA_EMPLOYEE . $this->endpointSuffixes[self::DELETE] . self::$vwaEmployee->getPersonId(),
            array(), array(), $this->defaultAdminHeaders
        );
        $this->assertStatusCode(200, $this->client);


        $postJson =
            json_encode(
                [
                    "first_name" => self::$vwaEmployee->getFirstName(),
                    "last_name" => self::$vwaEmployee->getLastName(),
                    "email_address" => self::$emailAddress,
                ]);

        $this->client->request('POST',
            Endpoint::VWA_EMPLOYEE . $this->endpointSuffixes[self::POST],
            array(), array(), $this->defaultAdminHeaders, $postJson
        );
        $this->assertStatusCode(200, $this->client);


        $editSuffix = '_EDIT_TEST_BY_ADMIN';
        $editJson =
            json_encode(
                [
                    "first_name" => self::$vwaEmployee->getFirstName().$editSuffix,
                    "last_name" => self::$vwaEmployee->getLastName().$editSuffix,
                    "email_address" => self::$emailAddress,
                    //password may only be edited by own vwaEmployee
                ]);

        $this->client->request('PUT',
            Endpoint::VWA_EMPLOYEE . $this->endpointSuffixes[self::PUT_byId]. self::$vwaEmployee->getPersonId(),
            array(), array(), $this->defaultAdminHeaders, $editJson
        );
        $this->assertStatusCode(200, $this->client);



        $editSuffix = '_EDIT_TEST_BY_OWN_VWA_EMPLOYEE';
        $newPassword = 'TheMortiestMorty@#$%^&*()';
        $editJson =
            json_encode(
                [
                    "first_name" => self::$vwaEmployee->getFirstName().$editSuffix,
                    "last_name" => self::$vwaEmployee->getLastName().$editSuffix,
                    "email_address" => self::$emailAddress,
                    "password" => $newPassword,
                ]);

        $this->client->request('PUT',
            Endpoint::VWA_EMPLOYEE . $this->endpointSuffixes[self::PUT_own],
            array(), array(), $this->vwaEmployeeHeaders, $editJson
        );
        $this->assertStatusCode(200, $this->client);



        $authorizationHeaders = [
            'PHP_AUTH_USER' => self::$emailAddress,
            'PHP_AUTH_PW'   => $newPassword,
        ];

        $this->client->request('GET',
            Endpoint::VWA_EMPLOYEE . $this->endpointSuffixes[self::GET_authorize],
            array(), array(), $authorizationHeaders
        );
        $this->assertStatusCode(200, $this->client);

    }


    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        parent::tearDown();

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



}