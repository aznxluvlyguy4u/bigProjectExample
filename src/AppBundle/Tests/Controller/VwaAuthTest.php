<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Client;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\DashboardType;
use AppBundle\Util\UnitTestData;
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
 * @group auth-vwa
 */
class VwaAuthTest extends WebTestCase
{
    const POST_password_reset_request = 'POST_password_reset_request';
    const GET_password_reset_confirmation = 'GET_password_reset_confirmation';

    const OLD_PASSWORD = 'oldPassWord12345';
    const NEW_PASSWORD = 'newPasswordTest24735980743';

    private $endpointSuffixes = [
        self::POST_password_reset_request => '/password-reset-token',
        self::GET_password_reset_confirmation => '/password-reset-token/', //{passwordResetToken}
    ];

    /** @var EntityManagerInterface|ObjectManager */
    static private $em;
    /** @var string */
    static private $emailAddress;
    /** @var VwaEmployee */
    static private $vwaEmployee;
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

        self::$vwaEmployee = UnitTestData::getOrCreateVwaEmployee(
            self::$em, self::$emailAddress,'Book','Enforcer');
        self::$vwaEmployee->setPassword(
            $this->getContainer()->get('security.password_encoder')
                ->encodePassword(self::$vwaEmployee, self::OLD_PASSWORD)
        );
        self::$em->persist(self::$vwaEmployee);
        self::$em->flush();
    }


    /**
     * @group auth-vwa
     * @group auth-password-reset
     */
    public function testPasswordReset()
    {
        $resetHeaders = [];

        $content = [
            'email_address' => self::$emailAddress,
            'dashboard_type' => DashboardType::VWA,
        ];

        $this->client->request(Request::METHOD_POST,
            Endpoint::AUTH . $this->endpointSuffixes[self::POST_password_reset_request],
            array(), array(), $resetHeaders, json_encode($content)
        );
        $this->assertStatusCode(200, $this->client);


        self::$em->refresh(self::$vwaEmployee);

        $this->client->request(Request::METHOD_GET,
            Endpoint::AUTH . $this->endpointSuffixes[self::GET_password_reset_confirmation] . self::$vwaEmployee->getPasswordResetToken(),
            array(), array(), $resetHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }



    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        parent::tearDown();

        $vwaEmployees = self::$em->getRepository(Client::class)->findBy(['emailAddress' => self::$emailAddress]);
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