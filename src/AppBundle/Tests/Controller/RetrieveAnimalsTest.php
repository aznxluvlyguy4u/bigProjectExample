<?php


namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

/**
 * Class RetrieveAnimalsTest
 * @package AppBundle\Tests\Controller
 * @group sync
 * @group animal-sync
 */
class RetrieveAnimalsTest extends WebTestCase
{
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
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
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
     * @group post
     * @group sync-post
     * @group animal-sync-post
     */
    public function testPostStandard()
    {
        $body = [
            // empty
        ];

        $json = json_encode($body);

        $this->client->request('POST',
            Endpoint::RETRIEVE_ANIMALS,
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
     * @group post
     * @group sync-post
     * @group animal-sync-post
     */
    public function testPostRvoLeading()
    {
        $body = [
            "is_rvo_leading" => true,
        ];

        $json = json_encode($body);

        $this->client->request('POST',
            Endpoint::RETRIEVE_ANIMALS,
            array(),
            array(),
            $this->defaultHeaders,
            $json
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

        $retrieveMessage = self::$em->getRepository(RetrieveAnimals::class)->findOneBy([
            'requestState' => RequestStateType::OPEN,
            'ubn' => self::$location->getUbn()], ['logDate' => 'DESC']);
        self::$em->remove($retrieveMessage);
        self::$em->flush();
    }
}