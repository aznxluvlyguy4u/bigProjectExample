<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Constant\Endpoint;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagTransferItemRequest;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;

/**
 * Class TagTransferTest
 * @package AppBundle\Tests\Controller
 * @group tag-transfer
 */
class TagTransferTest extends WebTestCase
{

    /** @var string */
    static private $accessTokenCode;
    /** @var Location */
    static private $location;
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
        $isLocalTestDatabase = Validator::isLocalTestDatabase(self::$em);
        if (!$isLocalTestDatabase) {
            dump(TestConstant::TEST_DB_ERROR_MESSAGE);
            die;
        }

        self::$location = UnitTestData::getActiveTestLocation(self::$em);
        self::$tag = UnitTestData::createTag(self::$em, self::$location);
        self::$accessTokenCode = self::$location->getCompany()->getOwner()->getAccessToken();
    }

    public static function tearDownAfterClass()
    {
        self::deleteTagTransfersOfTestTags();
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
     * @group tag-transfer-get
     * Test tag-transfer getter endpoints
     */
    public function testTagTransfersGetters()
    {
        //Get tags-transfers
        $this->client->request('GET',
            Endpoint::DECLARE_TAGS_TRANSFERS_ENDPOINT . '-history',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);

        $this->client->request('GET',
            Endpoint::DECLARE_TAGS_TRANSFERS_ENDPOINT . '-errors',
            array(), array(), $this->defaultHeaders
        );
        $this->assertStatusCode(200, $this->client);
    }

    /**
     * @group post
     * @group tag-transfer-post
     * Test tag-transfer post endpoint
     */
    public function testTagTransferPost()
    {
        $locationReceiver = UnitTestData::getRandomActiveLocation(self::$em, self::$location);
        $relationNumberAcceptant = $locationReceiver->getCompany()->getOwner()->getRelationNumberKeeper();
        $ubnNewOwner = $locationReceiver->getUbn();

        $declareMateJson =
            json_encode(
                [
                    "relation_number_acceptant" => $relationNumberAcceptant,
                    "ubn_new_owner" => $ubnNewOwner,
                    "tags" =>
                        [
                            [
                                "uln_country_code" => self::$tag->getUlnCountryCode(),
                                "uln_number" => self::$tag->getUlnNumber()
                            ]
                        ]
                ]);

        $this->client->request('POST',
            Endpoint::DECLARE_TAGS_TRANSFERS_ENDPOINT,
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



    private static function deleteTagTransfersOfTestTags()
    {
        $sql = "SELECT d.id FROM tag_transfer_item_request i
                  INNER JOIN transfer_requests j ON j.tag_transfer_item_request_id = i.id
                  INNER JOIN declare_tags_transfer d ON d.id = j.declare_tags_transfer_id
                WHERE i.uln_country_code = '".UnitTestData::ULN_COUNTRY_CODE."'";
        $results = self::$em->getConnection()->query($sql)->fetchAll();


        UnitTestData::deleteTestTags(self::$em->getConnection());


        if (count($results) > 0) {
            $declareTagTransferIds = SqlUtil::getSingleValueGroupedSqlResults('id', $results, true);

            foreach ($declareTagTransferIds as $declareTagTransferId)
            {
                /** @var DeclareTagsTransfer $declareTagTransfer */
                $declareTagTransfer = self::$em->getRepository(DeclareTagsTransfer::class)->find($declareTagTransferId);

                foreach ($declareTagTransfer->getTags() as $tag)
                {
                    self::$em->remove($tag);
                }

                self::$em->remove($declareTagTransfer);
            }
            self::$em->flush();
        }
    }
}