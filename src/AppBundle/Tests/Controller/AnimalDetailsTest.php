<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AnimalDetailsTest
 * @package AppBundle\Tests\Controller
 * @group animal
 * @group animal-details
 */
class AnimalDetailsTest extends WebTestCase
{
    const GET = 'GET';
    const PUT = 'PUT';
    const ADMIN_ENV_SUFFIX = '?is_admin_env=true';

    private $endpointSuffixes = [
        self::GET => '-details/', //{uln}
        self::PUT => '-details/', //{uln}
    ];

    /** @var string */
    static private $adminAccessTokenCode;
    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
    /** @var Location */
    static private $otherLocation;
    /** @var Ram */
    static private $ram;
    /** @var Ewe */
    static private $ewe;
    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var RequestClient */
    private $client;
    /** @var array */
    private $defaultHeaders;
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

        UnitTestData::deleteTestAnimals(self::$em->getConnection());

        self::$adminAccessTokenCode = UnitTestData::getRandomAdminAccessTokenCode(self::$em,AccessLevelType::SUPER_ADMIN);

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
        self::$ram = UnitTestData::createTestRam(self::$em, self::$location);
        self::$ewe = UnitTestData::createTestEwe(self::$em, self::$location);
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();

        self::$otherLocation = UnitTestData::getRandomActiveLocation(self::$em, self::$location);
    }


    /*
     * Runs after all testcases ran and teardown
     */
    public static function tearDownAfterClass()
    {
        UnitTestData::deleteTestAnimals(self::$em->getConnection());
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

        $this->defaultAdminHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$adminAccessTokenCode,
        );
    }

    /**
     * @group get
     * @group animal-get
     * @group animal-details-get
     */
    public function testGetters()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::GET] . self::$ram->getUln(),
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(Response::HTTP_OK, $this->client);


        $this->client->request(Request::METHOD_GET,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::GET] . self::$ram->getUln().self::ADMIN_ENV_SUFFIX,
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(Response::HTTP_UNAUTHORIZED, $this->client);


        $this->client->request(Request::METHOD_GET,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::GET] . self::$ram->getUln().self::ADMIN_ENV_SUFFIX,
            array(), array(), $this->defaultAdminHeaders
        );
        $this->assertStatusCode(Response::HTTP_OK, $this->client);
    }


    /**
     * @group put
     * @group animal-put
     * @group animal-details-put
     */
    public function testEditByClient()
    {
        $json =
            json_encode(
                [
                    "collar" => [
                        "number" => 9182,
                        "color" => 'TEAL'
                    ]
                ]);

        $this->client->request(Request::METHOD_PUT,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::PUT] . self::$ram->getUln(),
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(Response::HTTP_OK, $this->client);


        $json =
            json_encode(
                [
                    "is_admin_env" => true,
                    "animal" => [
                        "pedigree_country_code" => self::$ram->getPedigreeCountryCode(),
                        "pedigree_number" => self::$ram->getPedigreeNumber(),
                        "name" => self::$ram->getName(),
                        "nickname" => self::$ram->getNickname(),
                        "ubn_of_birth" => self::$ram->getUbn(),
                        "date_of_birth" => TimeUtil::getTimeStampForJsonBody(self::$ram->getDateOfBirth()),
//                        "parent_father" => [
//                            "uln_number" => "561720000381",
//                            "uln_country_code" => "DK",
//                            "type" => "Ram"
//                        ],
                        "parent_mother" => [
                            "uln_number" => self::$ewe->getUlnNumber(),
                            "uln_country_code" => self::$ewe->getUlnCountryCode(),
                            "type" => "Ewe"
                        ],
                        "location" => [
                            "ubn" => self::$otherLocation->getUbn(),
                        ],
                        "is_alive" => false,
                        "uln_number" => self::$ram->getUlnNumber(),
                        "uln_country_code" => self::$ram->getUlnCountryCode(),
                        "animal_order_number" => self::$ram->getAnimalOrderNumber(),
                        "breed_type" => self::$ram->getBreedType(),
                        "breed_code" => self::$ram->getBreedCode(),
                        "scrapie_genotype" => self::$ram->getScrapieGenotype(),
                        "pedigree_register" => [
                            "id" => 5
                        ],
                        "birth_progress" => self::$ram->getBirthProgress(),
                        "type" => "Ram"
                    ]
                ]);

        $this->client->request(Request::METHOD_PUT,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::PUT] . self::$ram->getUln(),
            array(),
            array(),
            $this->defaultAdminHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(Response::HTTP_OK, $this->client);
    }


    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        parent::tearDown();
    }
}