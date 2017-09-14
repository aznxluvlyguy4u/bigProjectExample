<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Client;
use AppBundle\Enumerator\DashboardType;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthTest
 *
 * @package AppBundle\Tests\Controller
 * @group auth
 * @group auth-client
 */
class AuthTest extends WebTestCase
{
    const PUT_password_reset = 'PUT_password_reset';
    const POST_password_reset_request = 'POST_password_reset_request';
    const GET_password_reset_confirmation = 'GET_password_reset_confirmation';
    const GET_authorize = 'GET_authorize';
    const PUT_password_change = 'PUT_password_change';

    const OLD_PASSWORD = 'oldPassWord12345';
    const NEW_PASSWORD = 'newPasswordTest24735980743';

    private $endpointSuffixes = [
        self::PUT_password_reset => '/password-reset',
        self::POST_password_reset_request => '/password-reset-token',
        self::GET_password_reset_confirmation => '/password-reset-token/', //{passwordResetToken}
        self::GET_authorize => '/authorize',
        self::PUT_password_change => '/password-change',
    ];

    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var string */
    static private $emailAddress;
    /** @var Client */
    static private $nsfoClient;
    /** @var RequestClient */
    private $client;


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

        self::$nsfoClient = new Client('Sheep', 'Master', self::$emailAddress);
        self::$nsfoClient->setPassword(
            $this->getContainer()->get('security.password_encoder')
                ->encodePassword(self::$nsfoClient, self::OLD_PASSWORD)
        );
        self::$em->persist(self::$nsfoClient);
        self::$em->flush();
    }


    /**
     * @group auth-client
     * @group auth-password-reset
     */
    public function testPasswordReset()
    {
        $resetHeaders = [];

        $content = [
            'email_address' => self::$emailAddress,
            'dashboard_type' => DashboardType::CLIENT,
        ];

        $this->client->request(Request::METHOD_PUT,
            Endpoint::AUTH . $this->endpointSuffixes[self::PUT_password_reset],
            array(), array(), $resetHeaders, json_encode($content)
        );
        $this->assertStatusCode(200, $this->client);


        self::$em->refresh(self::$nsfoClient);

        $this->client->request(Request::METHOD_GET,
            Endpoint::AUTH . $this->endpointSuffixes[self::GET_password_reset_confirmation] . self::$nsfoClient->getPasswordResetToken(),
            array(), array(), $resetHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }


    /**
     * @group auth-client
     * @group auth-password-update-and-authorization
     */
    public function testPasswordUpdateAndAuthorization()
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCESSTOKEN' => self::$nsfoClient->getAccessToken(),
        ];

        $jsonBody = json_encode([
            'current_password' => base64_encode(self::OLD_PASSWORD),
            'new_password' => base64_encode(self::NEW_PASSWORD),
        ]);

        $this->client->request(Request::METHOD_PUT,
            Endpoint::AUTH . $this->endpointSuffixes[self::PUT_password_change],
            array(), array(), $headers, $jsonBody
        );
        $this->assertStatusCode(200, $this->client);


        $authorizationHeaders = [
            'PHP_AUTH_USER' => self::$emailAddress,
            'PHP_AUTH_PW'   => self::NEW_PASSWORD,
        ];

        $this->client->request(Request::METHOD_GET,
            Endpoint::AUTH . $this->endpointSuffixes[self::GET_authorize],
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

        $nsfoClients = self::$em->getRepository(Client::class)->findBy(['emailAddress' => self::$emailAddress]);
        /** @var Client $nsfoClient */
        foreach ($nsfoClients as $nsfoClient) {
            $id = $nsfoClient->getId();

            $sql = "DELETE FROM action_log WHERE action_by_id = $id OR user_account_id = $id";
            self::$em->getConnection()->exec($sql);

            self::$em->remove($nsfoClient);
            self::$em->flush();
        }
    }



}