<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\GenderType;
use AppBundle\Service\BirthService;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BirthTest
 * @package AppBundle\Tests\Controller
 * @group birth
 */
class BirthTest extends WebTestCase
{
    const TEST_BIRTH_CUD = true;

    const GET_CandidateMothers = 'GET_CandidateMothers';
    const GET_CandidateFathers = 'GET_CandidateFathers';
    const GET_CandidateSurrogates = 'GET_CandidateSurrogates';

    private $endpointSuffixes = [
        self::GET_CandidateMothers => '/candidate-mothers',
        self::GET_CandidateFathers => '/candidate-fathers', //prepend with /{ulnMother}
        self::GET_CandidateSurrogates => '/candidate-surrogates', //prepend with /{ulnMother}
    ];

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
    /** @var Ewe */
    static private $ewe;
    /** @var Ram */
    static private $ram;
    /** @var Tag */
    static private $tag;
    /** @var IRSerializer */
    static private $serializer;
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
        self::$serializer = $container->get('app.serializer.ir');
        self::$em = $container->get('doctrine')->getManager();

        //Database safety check
        Validator::isTestDatabase(self::$em);

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
        self::$ram = UnitTestData::createTestRam(self::$em, self::$location);
        self::$ewe = UnitTestData::createTestEwe(self::$em, self::$location);
        self::$tag = UnitTestData::createTag(self::$em, self::$location);
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
    }

    public static function tearDownAfterClass()
    {
        self::$em->refresh(self::$ewe);
        foreach (self::$ewe->getMatings() as $mating) {
            self::$em->remove($mating);
        }
        self::$em->flush();

        /** @var Litter $litter */
        foreach (self::$ewe->getLitters() as $litter) {

            $litter->setAnimalMother(null);
            $litter->setAnimalFather(null);
            self::$em->persist($litter);

            /** @var Animal $child */
            foreach ($litter->getChildren() as $child) {
                $child->setLitter(null);
                self::$em->persist($child);
                self::$em->flush();
            }

            /** @var Animal $child */
            foreach ($litter->getChildren() as $child) {
                self::$em->remove($child);
            }

            foreach ($litter->getStillborns() as $stillborn) {
                self::$em->remove($stillborn);
            }

            self::$em->remove($litter);
            self::$em->flush();
        }

        self::$em->remove(self::$ewe);
        self::$em->remove(self::$ram);
        self::$em->flush();

        DoctrineUtil::updateTableSequence(self::$em->getConnection(),
            [Animal::getTableName(), DeclareBase::getTableName(), DeclareNsfoBase::getTableName(), Mate::getTableName(),
                Weight::getTableName(),TailLength::getTableName()]);
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
     * @group birth-get
     * Test birth getter endpoints
     */
    public function testBirthsGetters()
    {
        $this->client->request(Request::METHOD_GET,
            Endpoint::DECLARE_BIRTH_ENDPOINT,
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group get
     * @group birth-get
     */
    public function testCandidatePosts()
    {
        $eventDate = new \DateTime('2017-01-04');

        $json =
            json_encode(
                [
                    "date_of_birth" => TimeUtil::getTimeStampForJsonBody($eventDate),
                ]);

        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_BIRTH_ENDPOINT . $this->endpointSuffixes[self::GET_CandidateMothers],
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);



        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_BIRTH_ENDPOINT . '/' . self::$ewe->getUln() . $this->endpointSuffixes[self::GET_CandidateFathers],
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);



        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_BIRTH_ENDPOINT . '/' . self::$ewe->getUln() . $this->endpointSuffixes[self::GET_CandidateSurrogates],
            array(),
            array(),
            $this->defaultHeaders,
            $json
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group cud
     * @group birth-cud
     * Test birth post endpoint
     */
    public function testBirthPost()
    {
        $eventDate = new \DateTime('2017-01-04');

        $declareMateJson =
            json_encode(
                [
                    "start_date" => TimeUtil::getTimeStampForJsonBody($eventDate),
                    "end_date" => TimeUtil::getTimeStampForJsonBody($eventDate),
                    "ki" => false,
                    "pmsg" => false,
                    "ram" => [
                        "uln_country_code" => self::$ram->getUlnCountryCode(),
                        "uln_number" => self::$ram->getUlnNumber()
                    ],
                    "ewe" => [
                        "uln_country_code" => self::$ewe->getUlnCountryCode(),
                        "uln_number" => self::$ewe->getUlnNumber()
                    ]
                ]);

        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_MATINGS_ENDPOINT,
            array(),
            array(),
            $this->defaultHeaders,
            $declareMateJson
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertStatusCode(200, $this->client);

        $eventDate->add(new \DateInterval('P'.BirthService::MEDIAN_PREGNANCY_DAYS.'D'));

        $declareMateJson =
            json_encode(
                [
                    "mother" => [
                        "uln_country_code" => self::$ewe->getUlnCountryCode(),
                        "uln_number" => self::$ewe->getUlnNumber()
                    ],
                    "father" => [
                        "uln_country_code" => self::$ram->getUlnCountryCode(),
                        "uln_number" => self::$ram->getUlnNumber()
                    ],
                    "children" => [
                        0 => [
                            "uln_country_code" => self::$tag->getUlnCountryCode(),
                            "uln_number" => self::$tag->getUlnNumber(),
                            "is_alive" => true,
                            "nurture_type" => "NONE",
                            "gender" => GenderType::MALE,
                            "birth_progress" => "NO HELP",
                            "birth_weight" => 1.01,
                            "tail_length" => 0.1,
                        ]
                    ],
                    "date_of_birth" => TimeUtil::getTimeStampForJsonBody($eventDate),
                    "is_aborted" => false,
                    "is_pseudo_pregnancy" => false,
                    "litter_size" => 1,
                    "stillborn_count" => 0
                ]
            );

        $this->client->request(Request::METHOD_POST,
            Endpoint::DECLARE_BIRTH_ENDPOINT,
            array(),
            array(),
            $this->defaultHeaders,
            $declareMateJson
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
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