<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Entity\TreatmentType;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreatmentTypeTest
 *
 * @package AppBundle\Tests\Controller
 * @group treatment
 * @group treatment-template
 */
class TreatmentTypeTest extends WebTestCase
{
    const GET = 'GET';
    const POST = 'POST';
    const EDIT = 'EDIT';
    const DELETE = 'DELETE';

    private $endpointSuffixes = [
        self::GET => '',
        self::POST => '',
        self::EDIT => '/',//{treatmentTypeId}
        self::DELETE => '/',//{treatmentTypeId}
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
        Validator::isTestDatabase(self::$em);

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
     * @group treatment-template-type-get
     */
    public function testGet()
    {
        //Get tags-transfers
        $this->client->request(Request::METHOD_GET,
            Endpoint::TREATMENT_TYPES . $this->endpointSuffixes[self::GET],
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }

    /**
     * @group cud
     * @group treatment-template-cud
     */
    public function testCreateUpdateDelete()
    {
        $json =
            json_encode(
                [
                    "description" => self::$faker->name,
                    "type" => TreatmentTypeOption::LOCATION,
                ]);

        $this->client->request(Request::METHOD_POST,
            Endpoint::TREATMENT_TYPES . $this->endpointSuffixes[self::POST],
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $this->assertStatusCode(200, $this->client);

        if ($response->getStatusCode() === 200) {
            $data = ResultUtil::getResultArray($response);
            $id = ArrayUtil::get('id', $data);
            $description = ArrayUtil::get('description', $data);
            $newDescription = $description.'213546u7rteyw';

            $json =
                json_encode(
                    [
                        "description" => $newDescription,
                    ]);

            $this->client->request(Request::METHOD_PUT,
                Endpoint::TREATMENT_TYPES . $this->endpointSuffixes[self::EDIT] . $id,
                array(),
                array(),
                $this->defaultHeaders,
                $json
            );

            $response = $this->client->getResponse();
            $updatedDescription = ResultUtil::getFromResult('description', $response);

            $this->assertEquals($newDescription, $updatedDescription);
            $this->assertStatusCode(200, $this->client);


            $this->client->request(Request::METHOD_DELETE,
                Endpoint::TREATMENT_TYPES . $this->endpointSuffixes[self::DELETE] . $id,
                array(),
                array(),
                $this->defaultHeaders,
                $json
            );

            $response = $this->client->getResponse();
            $isActive = ResultUtil::getFromResult('is_active', $response);
            $this->assertEquals($isActive, false);
            $this->assertStatusCode(200, $this->client);

            self::$treatmentType = self::$em->getRepository(TreatmentType::class)->find($id);
        }
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