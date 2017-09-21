<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AdminTest
 * @package AppBundle\Tests\Controller
 * @group admin
 */
class AdminTest extends WebTestCase
{
    const GET_getAdmins = 'GET_getAdmins';
    const GET_getAccessLevelTypes = 'GET_getAccessLevelTypes';
    const POST_createAdmin = 'POST_createAdmin';
    const PUT_editAdmin = 'PUT_editAdmin';
    const PUT_deactivateAdmin = 'PUT_deactivateAdmin';
    const POST_getTemporaryGhostToken = 'POST_getTemporaryGhostToken';
    const PUT_verifyGhostToken = 'PUT_verifyGhostToken';

    private $endpointSuffixes = [
        self::GET_getAdmins => '',
        self::GET_getAccessLevelTypes => '-access-levels',
        self::POST_createAdmin => '',
        self::PUT_editAdmin => '',
        self::PUT_deactivateAdmin => '-deactivate',
        self::POST_getTemporaryGhostToken => '/ghost',
        self::PUT_verifyGhostToken => '/verify-ghost-token',
    ];


    /** @var RequestClient */
    private $client;
    /** @var string */
    static private $accessTokenCode;
    /** @var IRSerializer */
    static private $serializer;
    /** @var ObjectManager|EntityManagerInterface */
    static private $em;
    /** @var Employee */
    static private $employee;
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

        self::$accessTokenCode = UnitTestData::getRandomAdminAccessTokenCode(self::$em, AccessLevelType::SUPER_ADMIN);

        $testEmail = $container->getParameter('test_email');
        self::$employee = UnitTestData::getTestAdmin($testEmail, AccessLevelType::ADMIN);
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
     * @group admin-get
     * Test admin getter endpoints
     */
    public function testAdminsGetters()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::ADMIN . $this->endpointSuffixes[self::GET_getAdmins],
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);


        $this->client->request(Request::METHOD_GET,
            Endpoint::ADMIN . $this->endpointSuffixes[self::GET_getAccessLevelTypes],
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group cud
     * @group admin-cud
     * Test admin create, edit and delete endpoints
     */
    public function testCreateEditDeleteAdmin()
    {

        /* Create */

        $createAdminJson =
            json_encode(
                [
                    "first_name" => self::$employee->getFirstName(),
                    "last_name" => self::$employee->getLastName(),
                    "email_address" => self::$employee->getEmailAddress(),
                    "access_level" => self::$employee->getAccessLevel(),
                ]);

        $this->client->request(Request::METHOD_POST,
            Endpoint::ADMIN . $this->endpointSuffixes[self::POST_createAdmin],
            array(),
            array(),
            $this->defaultHeaders,
            $createAdminJson
        );

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey(Constant::RESULT_NAMESPACE, $data);

        $personId = ResultUtil::getFromResult('person_id', $response);

        $this->assertEquals(true, is_string($personId), "'person_id' value is missing in result array");
        $this->assertStatusCode(200, $this->client);


        if ($personId !== null) {

            self::$employee->setPersonId($personId);

            /* Edit */

            $newFirstName = self::$employee->getFirstName() . '_TESTING170489';
            $newLastName = self::$employee->getLastName() . '_TESTING170489';
            $newAccessLevel = self::$employee->getAccessLevel() === AccessLevelType::ADMIN ?
                AccessLevelType::SUPER_ADMIN : AccessLevelType::ADMIN;

            $editAdminJson =
                json_encode(
                    [
                        "person_id" => $personId,
                        "first_name" => $newFirstName,
                        "last_name" => $newLastName,
                        "email_address" => self::$employee->getEmailAddress(),
                        "access_level" => $newAccessLevel,
                    ]);

            $this->client->request(Request::METHOD_PUT,
                Endpoint::ADMIN . $this->endpointSuffixes[self::PUT_editAdmin],
                array(),
                array(),
                $this->defaultHeaders,
                $editAdminJson
            );

            /** @var JsonResponse $response */
            $response = $this->client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertArrayHasKey(Constant::RESULT_NAMESPACE, $data);
            $this->assertStatusCode(200, $this->client);

            $resultArray = ResultUtil::getResultArray($response);

            $this->assertEquals(true, is_array($resultArray), "'Result' array is missing in edit output");
            if (is_array($resultArray)) {
                $this->assertEquals($newFirstName, ArrayUtil::get('first_name', $resultArray), "'first_name' is not updated");
                $this->assertEquals($newLastName, ArrayUtil::get('last_name', $resultArray), "'last_name' is not updated");
                $this->assertEquals($newAccessLevel, ArrayUtil::get('access_level', $resultArray), "'access_level' is not updated");
            }

            /* Deactivate */

            $deleteAdminJson =
                json_encode(
                    [
                        "person_id" => $personId,
                    ]);

            $this->client->request(Request::METHOD_PUT,
                Endpoint::ADMIN . $this->endpointSuffixes[self::PUT_deactivateAdmin],
                array(),
                array(),
                $this->defaultHeaders,
                $deleteAdminJson
            );

            /** @var JsonResponse $response */
            $response = $this->client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertStatusCode(200, $this->client);

            $resultArray = ResultUtil::getResultArray($response);

            $entityIsReturnedInJsonData = false;

            if ($entityIsReturnedInJsonData) {

                $this->assertArrayHasKey(Constant::RESULT_NAMESPACE, $data);

                $this->assertEquals(true, is_array($resultArray), "'Result' array is missing in delete output");
                if (is_array($resultArray)) {
                    $this->assertEquals(false, ArrayUtil::get('is_active', $resultArray), "Admin has not been deactivated");
                }

            } else {
                $this->assertEquals('ok', $resultArray, "output does not equal 'ok'");
            }

        }

    }


    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /*
     * Runs after all testcases ran and teardown
     */
    public static function tearDownAfterClass()
    {
        if (self::$employee->getPersonId() !== null) {
            /** @var Employee $employee */
            $employee = self::$em->getRepository(Employee::class)
                ->findOneByPersonId(self::$employee->getPersonId());

            if ($employee) {
                $id = $employee->getId();
                $sql = "DELETE FROM action_log WHERE user_account_id = $id OR action_by_id = $id";
                self::$em->getConnection()->query($sql)->execute();

                self::$em->remove($employee);
                self::$em->flush();
            }

            DoctrineUtil::updateTableSequence(self::$em->getConnection(), ['person']);
        }
    }
}