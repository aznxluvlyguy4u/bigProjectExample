<?php


namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RetrieveAnimalsTest
 * @package AppBundle\Tests\Controller
 * @group sync
 * @group animal-sync
 * @group rvo
 */
class RetrieveAnimalsTest extends WebTestCase
{
    /** @var string */
    static private $accessTokenCode;
    /** @var Token */
    static private $superAdminGhostToken;
    /** @var Location */
    static private $location;
    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var RequestClient */
    private $client;
    /** @var array */
    private $defaultHeaders;
    /** @var array */
    private $superAdminHeaders;


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

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();

        self::$superAdminGhostToken = UnitTestData::getRandomGhostToken(self::$em, AccessLevelType::SUPER_ADMIN);
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

        $this->superAdminHeaders = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$superAdminGhostToken->getAdmin()->getAccessToken(),
            'HTTP_GHOSTTOKEN' => self::$superAdminGhostToken->getCode(),
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

        $this->client->request(Request::METHOD_POST,
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

        $this->client->request(Request::METHOD_POST,
            Endpoint::RETRIEVE_ANIMALS,
            array(),
            array(),
            $this->superAdminHeaders,
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