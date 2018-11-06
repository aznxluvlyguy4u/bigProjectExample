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
    const PUT_BATCH = 'PUT_BATCH';
    const ADMIN_ENV_SUFFIX = '?is_admin_env=true';

    private $endpointSuffixes = [
        self::GET => '-details/', //{uln}
        self::PUT => '-details/', //{uln}
        self::PUT_BATCH => '-details',
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
    }


    /**
     * @group put
     * @group animal-put
     * @group animal-details-put
     */
    public function testEditByAdminInSingleEditMode()
    {
        $json =
            json_encode(
                [
                    "is_admin_env" => true,
                    "animal" => [
                        "id" => self::$ram->getId(),
                        "pedigree_country_code" => self::$ram->getPedigreeCountryCode(),
                        "pedigree_number" => self::$ram->getPedigreeNumber(),
                        "name" => self::$ram->getName(),
                        "nickname" => self::$ram->getNickname(),
                        "ubn_of_birth" => self::$ram->getUbn(),
                        "date_of_birth" => TimeUtil::getTimeStampForJsonBody(self::$ram->getDateOfBirth()),
                        "parent_mother" => [
                            "id" => self::$ram->getParentMotherId(),
                        ],
                        "parent_father" => [
                            "id" => self::$ram->getParentFatherId(),
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
     * @group put
     * @group animal-put
     * @group animal-details-put
     */
    public function testEditByAdminInBatchEditMode()
    {
        // Switch the Ram and Ewe values
        
        $json =
            json_encode(
                [
                    "animals" => [
                        [
                            "id" => self::$ram->getId(),
                            "pedigree_country_code" => self::$ewe->getPedigreeCountryCode(),
                            "pedigree_number" => self::$ewe->getPedigreeNumber(),
                            "name" => self::$ewe->getName(),
                            "nickname" => self::$ewe->getNickname(),
                            "ubn_of_birth" => self::$ewe->getUbnOfBirth(),
                            "location_of_birth" => [
                                "location_id" => (self::$ewe->getLocationOfBirth() ?
                                    self::$ewe->getLocationOfBirth()->getLocationId() : null)
                            ],
                            "location" => [
                                "location_id" => (self::$ewe->getLocation() ?
                                    self::$ewe->getLocation()->getLocationId() : null)
                            ],
                            "date_of_birth" => TimeUtil::getTimeStampForJsonBody(self::$ewe->getDateOfBirth()),
                            "parent_mother" => [
                                "id" => self::$ewe->getParentMotherId(),
                            ],
                            "parent_father" => [
                                "id" => self::$ewe->getParentFatherId(),
                            ],
                            "is_alive" => false,
                            "lambar" => self::$ewe->getLambar(),
                            "uln_number" => self::$ewe->getUlnNumber(),
                            "uln_country_code" => self::$ewe->getUlnCountryCode(),
                            "breed_type" => self::$ewe->getBreedType(),
                            "breed_code" => self::$ewe->getBreedCode(),
                            "scrapie_genotype" => self::$ewe->getScrapieGenotype(),
                            "pedigree_register" => [
                                "id" => (self::$ewe->getPedigreeRegister() ? self::$ewe->getPedigreeRegister()->getId() : null)
                            ],
                            "litter" => [
                                "id" => self::$ewe->getLitter(),
                                "stillborn_count" => self::$ewe->getLitter() ? self::$ewe->getLitter()->getStillbornCount() : 0,
                                "born_alive_count" => self::$ewe->getLitter() ? self::$ewe->getLitter()->getBornAliveCount() : 0,
                                "animal_father" => [
                                    "id" => (self::$ewe->getLitter() && self::$ewe->getLitter()->getAnimalFather()
                                        ? self::$ewe->getLitter()->getAnimalFather()->getId() : null)
                                ],
                                "animal_mother" => [
                                    "id" => (self::$ewe->getLitter() && self::$ewe->getLitter()->getAnimalMother()
                                        ? self::$ewe->getLitter()->getAnimalMother()->getId() : null)
                                ],
                            ],
                            "birth_progress" => self::$ewe->getBirthProgress(),
                            "type" => self::$ram->getObjectType()
                        ],
                        [
                            "id" => self::$ewe->getId(),
                            "pedigree_country_code" => self::$ram->getPedigreeCountryCode(),
                            "pedigree_number" => self::$ram->getPedigreeNumber(),
                            "name" => self::$ram->getName(),
                            "nickname" => self::$ram->getNickname(),
                            "ubn_of_birth" => self::$ram->getUbnOfBirth(),
                            "location_of_birth" => [
                                "location_id" => (self::$ram->getLocationOfBirth() ?
                                    self::$ram->getLocationOfBirth()->getLocationId() : null)
                            ],
                            "location" => [
                                "location_id" => (self::$ram->getLocation() ?
                                    self::$ram->getLocation()->getLocationId() : null)
                            ],
                            "date_of_birth" => TimeUtil::getTimeStampForJsonBody(self::$ram->getDateOfBirth()),
                            "parent_mother" => [
                                "id" => self::$ram->getParentMotherId(),
                            ],
                            "parent_father" => [
                                "id" => self::$ram->getParentFatherId(),
                            ],
                            "is_alive" => false,
                            "lambar" => self::$ram->getLambar(),
                            "uln_number" => self::$ram->getUlnNumber(),
                            "uln_country_code" => self::$ram->getUlnCountryCode(),
                            "breed_type" => self::$ram->getBreedType(),
                            "breed_code" => self::$ram->getBreedCode(),
                            "scrapie_genotype" => self::$ram->getScrapieGenotype(),
                            "pedigree_register" => [
                                "id" => (self::$ram->getPedigreeRegister() ? self::$ram->getPedigreeRegister()->getId() : null)
                            ],
                            "litter" => [
                                "id" => self::$ram->getLitter(),
                                "stillborn_count" => self::$ram->getLitter() ? self::$ram->getLitter()->getStillbornCount() : 0,
                                "born_alive_count" => self::$ram->getLitter() ? self::$ram->getLitter()->getBornAliveCount() : 0,
                                "animal_father" => [
                                    "id" => (self::$ram->getLitter() && self::$ram->getLitter()->getAnimalFather()
                                        ? self::$ram->getLitter()->getAnimalFather()->getId() : null)
                                ],
                                "animal_mother" => [
                                    "id" => (self::$ram->getLitter() && self::$ram->getLitter()->getAnimalMother()
                                        ? self::$ram->getLitter()->getAnimalMother()->getId() : null)
                                ],
                            ],
                            "birth_progress" => self::$ram->getBirthProgress(),
                            "type" => self::$ewe->getObjectType()
                        ]
                    ]
                ]);

        $this->client->request(Request::METHOD_PUT,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::PUT_BATCH],
            array(),
            array(),
            $this->defaultAdminHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(Response::HTTP_OK, $this->client);


        // Now return the original values

        $json =
            json_encode(
                [
                    "animals" => [
                        [
                            "id" => self::$ram->getId(),
                            "pedigree_country_code" => self::$ram->getPedigreeCountryCode(),
                            "pedigree_number" => self::$ram->getPedigreeNumber(),
                            "name" => self::$ram->getName(),
                            "nickname" => self::$ram->getNickname(),
                            "ubn_of_birth" => self::$ram->getUbnOfBirth(),
                            "location_of_birth" => [
                                "location_id" => (self::$ram->getLocationOfBirth() ?
                                    self::$ram->getLocationOfBirth()->getLocationId() : null)
                            ],
                            "location" => [
                                "location_id" => (self::$ram->getLocation() ?
                                    self::$ram->getLocation()->getLocationId() : null)
                            ],
                            "date_of_birth" => TimeUtil::getTimeStampForJsonBody(self::$ram->getDateOfBirth()),
                            "parent_mother" => [
                                "id" => self::$ram->getParentMotherId(),
                            ],
                            "parent_father" => [
                                "id" => self::$ram->getParentFatherId(),
                            ],
                            "is_alive" => false,
                            "lambar" => self::$ram->getLambar(),
                            "uln_number" => self::$ram->getUlnNumber(),
                            "uln_country_code" => self::$ram->getUlnCountryCode(),
                            "breed_type" => self::$ram->getBreedType(),
                            "breed_code" => self::$ram->getBreedCode(),
                            "scrapie_genotype" => self::$ram->getScrapieGenotype(),
                            "pedigree_register" => [
                                "id" => (self::$ram->getPedigreeRegister() ? self::$ram->getPedigreeRegister()->getId() : null)
                            ],
                            "litter" => [
                                "id" => self::$ram->getLitter(),
                                "stillborn_count" => self::$ram->getLitter() ? self::$ram->getLitter()->getStillbornCount() : 0,
                                "born_alive_count" => self::$ram->getLitter() ? self::$ram->getLitter()->getBornAliveCount() : 0,
                                "animal_father" => [
                                    "id" => (self::$ram->getLitter() && self::$ram->getLitter()->getAnimalFather()
                                        ? self::$ram->getLitter()->getAnimalFather()->getId() : null)
                                ],
                                "animal_mother" => [
                                    "id" => (self::$ram->getLitter() && self::$ram->getLitter()->getAnimalMother()
                                        ? self::$ram->getLitter()->getAnimalMother()->getId() : null)
                                ],
                            ],
                            "birth_progress" => self::$ram->getBirthProgress(),
                            "type" => self::$ram->getObjectType()
                        ],
                        [
                            "id" => self::$ewe->getId(),
                            "pedigree_country_code" => self::$ewe->getPedigreeCountryCode(),
                            "pedigree_number" => self::$ewe->getPedigreeNumber(),
                            "name" => self::$ewe->getName(),
                            "nickname" => self::$ewe->getNickname(),
                            "ubn_of_birth" => self::$ewe->getUbnOfBirth(),
                            "location_of_birth" => [
                                "location_id" => (self::$ewe->getLocationOfBirth() ?
                                    self::$ewe->getLocationOfBirth()->getLocationId() : null)
                            ],
                            "location" => [
                                "location_id" => (self::$ewe->getLocation() ?
                                    self::$ewe->getLocation()->getLocationId() : null)
                            ],
                            "date_of_birth" => TimeUtil::getTimeStampForJsonBody(self::$ewe->getDateOfBirth()),
                            "parent_mother" => [
                                "id" => self::$ewe->getParentMotherId(),
                            ],
                            "parent_father" => [
                                "id" => self::$ewe->getParentFatherId(),
                            ],
                            "is_alive" => false,
                            "lambar" => self::$ewe->getLambar(),
                            "uln_number" => self::$ewe->getUlnNumber(),
                            "uln_country_code" => self::$ewe->getUlnCountryCode(),
                            "breed_type" => self::$ewe->getBreedType(),
                            "breed_code" => self::$ewe->getBreedCode(),
                            "scrapie_genotype" => self::$ewe->getScrapieGenotype(),
                            "pedigree_register" => [
                                "id" => (self::$ewe->getPedigreeRegister() ? self::$ewe->getPedigreeRegister()->getId() : null)
                            ],
                            "litter" => [
                                "id" => self::$ewe->getLitter(),
                                "stillborn_count" => self::$ewe->getLitter() ? self::$ewe->getLitter()->getStillbornCount() : 0,
                                "born_alive_count" => self::$ewe->getLitter() ? self::$ewe->getLitter()->getBornAliveCount() : 0,
                                "animal_father" => [
                                    "id" => (self::$ewe->getLitter() && self::$ewe->getLitter()->getAnimalFather()
                                        ? self::$ewe->getLitter()->getAnimalFather()->getId() : null)
                                ],
                                "animal_mother" => [
                                    "id" => (self::$ewe->getLitter() && self::$ewe->getLitter()->getAnimalMother()
                                        ? self::$ewe->getLitter()->getAnimalMother()->getId() : null)
                                ],
                            ],
                            "birth_progress" => self::$ewe->getBirthProgress(),
                            "type" => self::$ewe->getObjectType()
                        ]
                    ]
                ]);

        $this->client->request(Request::METHOD_PUT,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::PUT_BATCH],
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
     * @group put
     * @group animal-put
     * @group animal-details-put
     * @group animal-details-put-failed
     */
    public function testEditByAdminInBatchEditModeBlockDuplicateUlns()
    {
        $sql = "SELECT uln_country_code, uln_number FROM animal
                WHERE 
                  uln_country_code NOTNULL AND uln_country_code <> '".self::$ram->getUlnCountryCode()."' AND 
                  uln_number NOTNULL AND uln_number <> '".self::$ram->getUlnNumber()."' 
                LIMIT 1
                ";
        $ulnResult = self::$em->getConnection()->query($sql)->fetch();

        $duplicateUlnCountryCode = $ulnResult['uln_country_code'];
        $duplicateUlnNumber = $ulnResult['uln_number'];

        $json =
            json_encode(
                [
                    "animals" => [
                        [
                            "id" => self::$ram->getId(),
                            "pedigree_country_code" => self::$ram->getPedigreeCountryCode(),
                            "pedigree_number" => self::$ram->getPedigreeNumber(),
                            "name" => self::$ram->getName(),
                            "nickname" => self::$ram->getNickname(),
                            "ubn_of_birth" => self::$ram->getUbnOfBirth(),
                            "location_of_birth" => [
                                "location_id" => (self::$ram->getLocationOfBirth() ?
                                    self::$ram->getLocationOfBirth()->getLocationId() : null)
                            ],
                            "location" => [
                                "location_id" => (self::$ram->getLocation() ?
                                    self::$ram->getLocation()->getLocationId() : null)
                            ],
                            "date_of_birth" => TimeUtil::getTimeStampForJsonBody(self::$ram->getDateOfBirth()),
                            "parent_mother" => [
                                "id" => self::$ram->getParentMotherId(),
                            ],
                            "parent_father" => [
                                "id" => self::$ram->getParentFatherId(),
                            ],
                            "is_alive" => false,
                            "lambar" => self::$ram->getLambar(),
                            "uln_number" => $duplicateUlnNumber,
                            "uln_country_code" => $duplicateUlnCountryCode,
                            "breed_type" => self::$ram->getBreedType(),
                            "breed_code" => self::$ram->getBreedCode(),
                            "scrapie_genotype" => self::$ram->getScrapieGenotype(),
                            "pedigree_register" => [
                                "id" => (self::$ram->getPedigreeRegister() ? self::$ram->getPedigreeRegister()->getId() : null)
                            ],
                            "litter" => [
                                "id" => self::$ram->getLitter(),
                                "stillborn_count" => self::$ram->getLitter() ? self::$ram->getLitter()->getStillbornCount() : 0,
                                "born_alive_count" => self::$ram->getLitter() ? self::$ram->getLitter()->getBornAliveCount() : 0,
                                "animal_father" => [
                                    "id" => (self::$ram->getLitter() && self::$ram->getLitter()->getAnimalFather()
                                        ? self::$ram->getLitter()->getAnimalFather()->getId() : null)
                                ],
                                "animal_mother" => [
                                    "id" => (self::$ram->getLitter() && self::$ram->getLitter()->getAnimalMother()
                                        ? self::$ram->getLitter()->getAnimalMother()->getId() : null)
                                ],
                            ],
                            "birth_progress" => self::$ram->getBirthProgress(),
                            "type" => self::$ram->getObjectType()
                        ]
                    ]
                ]);

        $this->client->request(Request::METHOD_PUT,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::PUT_BATCH],
            array(),
            array(),
            $this->defaultAdminHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(Response::HTTP_PRECONDITION_REQUIRED, $this->client);
    }


    /**
     * @group put
     * @group animal-put
     * @group animal-details-put
     * @group animal-details-put-failed
     */
    public function testEditByAdminInBatchEditModeBlockDuplicateStns()
    {
        if (self::$ram->getPedigreeCountryCode() === null || self::$ram->getPedigreeNumber() === null) {
            $sql = "SELECT pedigree_country_code, pedigree_number FROM animal
                WHERE 
                  pedigree_country_code NOTNULL AND pedigree_number NOTNULL
                LIMIT 1
                ";

        } else {
            $sql = "SELECT pedigree_country_code, pedigree_number FROM animal
                WHERE 
                  pedigree_country_code NOTNULL AND pedigree_country_code <> '".self::$ram->getPedigreeCountryCode()."' AND 
                  pedigree_number NOTNULL AND pedigree_number <> '".self::$ram->getPedigreeNumber()."' 
                LIMIT 1
                ";
        }

        $pedigreeResult = self::$em->getConnection()->query($sql)->fetch();

        $duplicatePedigreeCountryCode = $pedigreeResult['pedigree_country_code'];
        $duplicatePedigreeNumber = $pedigreeResult['pedigree_number'];

        $json =
            json_encode(
                [
                    "animals" => [
                        [
                            "id" => self::$ram->getId(),
                            "pedigree_country_code" => $duplicatePedigreeCountryCode,
                            "pedigree_number" => $duplicatePedigreeNumber,
                            "name" => self::$ram->getName(),
                            "nickname" => self::$ram->getNickname(),
                            "ubn_of_birth" => self::$ram->getUbnOfBirth(),
                            "location_of_birth" => [
                                "location_id" => (self::$ram->getLocationOfBirth() ?
                                    self::$ram->getLocationOfBirth()->getLocationId() : null)
                            ],
                            "location" => [
                                "location_id" => (self::$ram->getLocation() ?
                                    self::$ram->getLocation()->getLocationId() : null)
                            ],
                            "date_of_birth" => TimeUtil::getTimeStampForJsonBody(self::$ram->getDateOfBirth()),
                            "parent_mother" => [
                                "id" => self::$ram->getParentMotherId(),
                            ],
                            "parent_father" => [
                                "id" => self::$ram->getParentFatherId(),
                            ],
                            "is_alive" => false,
                            "lambar" => self::$ram->getLambar(),
                            "uln_number" => self::$ewe->getUlnNumber(),
                            "uln_country_code" => self::$ewe->getUlnCountryCode(),
                            "breed_type" => self::$ram->getBreedType(),
                            "breed_code" => self::$ram->getBreedCode(),
                            "scrapie_genotype" => self::$ram->getScrapieGenotype(),
                            "pedigree_register" => [
                                "id" => (self::$ram->getPedigreeRegister() ? self::$ram->getPedigreeRegister()->getId() : null)
                            ],
                            "litter" => [
                                "id" => self::$ram->getLitter(),
                                "stillborn_count" => self::$ram->getLitter() ? self::$ram->getLitter()->getStillbornCount() : 0,
                                "born_alive_count" => self::$ram->getLitter() ? self::$ram->getLitter()->getBornAliveCount() : 0,
                                "animal_father" => [
                                    "id" => (self::$ram->getLitter() && self::$ram->getLitter()->getAnimalFather()
                                        ? self::$ram->getLitter()->getAnimalFather()->getId() : null)
                                ],
                                "animal_mother" => [
                                    "id" => (self::$ram->getLitter() && self::$ram->getLitter()->getAnimalMother()
                                        ? self::$ram->getLitter()->getAnimalMother()->getId() : null)
                                ],
                            ],
                            "birth_progress" => self::$ram->getBirthProgress(),
                            "type" => self::$ram->getObjectType()
                        ]
                    ]
                ]);

        $this->client->request(Request::METHOD_PUT,
            Endpoint::ANIMALS . $this->endpointSuffixes[self::PUT_BATCH],
            array(),
            array(),
            $this->defaultAdminHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(Response::HTTP_PRECONDITION_REQUIRED, $this->client);
    }


    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        parent::tearDown();
    }
}