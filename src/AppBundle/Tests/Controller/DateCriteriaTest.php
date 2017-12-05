<?php

namespace AppBundle\Tests\Util;


use AppBundle\Component\Utils;
use AppBundle\Entity\Location;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Util\DateCriteria;
use AppBundle\Util\UnitTestData;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DateCriteriaTest
 *
 * @group util
 * @group doctrine
 * @group date-criteria
 */
class DateCriteriaTest extends WebTestCase
{
    /** @var ContainerInterface */
    private static $container;

    /** @var Location */
    private static $location;
    /** @var array */
    private static $tags;

    /**
     * Runs before class setup
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        //start the symfony kernel
        $kernel = static::createKernel();
        $kernel->boot();

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        //Get the DI container
        self::$container = $kernel->getContainer();

        //Database safety check
        Validator::isTestDatabase(self::getManager());

        UnitTestData::deleteTestTags(self::getConnection());

        self::$location = UnitTestData::getActiveTestLocation(self::getManager());
        self::createTestTags();
    }


    /**
     * @return ObjectManager|EntityManagerInterface
     */
    protected static function getManager()
    {
        return self::$container->get('doctrine')->getManager();
    }


    /**
     * @return Connection
     */
    protected static function getConnection()
    {
        return self::getManager()->getConnection();
    }


    protected static function createTestTags()
    {
        $orderDates = [
            '2022-01-01 23:59:59',
            '2022-01-02 00:00:00',
            '2022-01-02 12:00:00',
            '2022-01-02 23:59:59',
            '2022-01-03 00:00:00',
            '2022-01-03 12:00:00',
            '2022-01-03 23:59:59',
            '2022-01-04 00:00:00',
            '2022-01-04 12:00:00',
            '2022-01-04 23:59:59',
            '2022-01-05 00:00:00',
            '2022-01-05 12:00:00',
            '2022-01-05 23:59:59',
            '2022-01-06 00:00:00',
            '2022-01-07 23:59:59',
        ];

        foreach ($orderDates as $orderDate) {
            self::$tags[] = UnitTestData::createTag(
                    self::getManager(),
                    self::$location,
                    self::getRandomUlnNumber(),
                    TagStateType::UNASSIGNED,
                    $orderDate
                );
        }
    }


    /**
     * @return string
     */
    protected static function getRandomUlnNumber()
    {
        return '99' . Utils::randomString(UnitTestData::ULN_NUMBER_LENGTH-2, UnitTestData::ULN_NUMBER_KEYSPACE);
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        UnitTestData::deleteTestTags(self::getConnection());
    }

    /**
     * Runs on each testcase
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        parent::tearDown();
    }


    /**
     * @group date-criteria-gt
     */
    public function testGt()
    {
        $date = new \DateTime('2022-01-03 11:00:00');

        $criteria = Criteria::create()
            ->where(DateCriteria::gt('orderDate', $date))
            ->andWhere(Criteria::expr()->eq('location', self::$location))
            ->andWhere(Criteria::expr()->eq('tagDescription', UnitTestData::TEST_TAG_LABEL))
            ->orderBy(['orderDate' => Criteria::ASC]);

        $tags = self::getManager()->getRepository(Tag::class)->matching($criteria);

        self::assertCount(8, $tags);
    }


    /**
     * @group date-criteria-gte
     */
    public function testGte()
    {
        $date = new \DateTime('2022-01-03 15:00:00');

        $criteria = Criteria::create()
            ->where(DateCriteria::gte('orderDate', $date))
            ->andWhere(Criteria::expr()->eq('location', self::$location))
            ->andWhere(Criteria::expr()->eq('tagDescription', UnitTestData::TEST_TAG_LABEL))
            ->orderBy(['orderDate' => Criteria::ASC]);

        $tags = self::getManager()->getRepository(Tag::class)->matching($criteria);

        self::assertCount(11, $tags);
    }


    /**
     * @group date-criteria-lt
     */
    public function testLt()
    {
        $date = new \DateTime('2022-01-04 23:59:59');

        $criteria = Criteria::create()
            ->where(DateCriteria::lt('orderDate', $date))
            ->andWhere(Criteria::expr()->eq('location', self::$location))
            ->andWhere(Criteria::expr()->eq('tagDescription', UnitTestData::TEST_TAG_LABEL))
            ->orderBy(['orderDate' => Criteria::ASC]);

        $tags = self::getManager()->getRepository(Tag::class)->matching($criteria);

        self::assertCount(7, $tags);
    }


    /**
     * @group date-criteria-lte
     */
    public function testLte()
    {
        $date = new \DateTime('2022-01-04 23:59:59');

        $criteria = Criteria::create()
            ->where(DateCriteria::lte('orderDate', $date))
            ->andWhere(Criteria::expr()->eq('location', self::$location))
            ->andWhere(Criteria::expr()->eq('tagDescription', UnitTestData::TEST_TAG_LABEL))
            ->orderBy(['orderDate' => Criteria::ASC]);

        $tags = self::getManager()->getRepository(Tag::class)->matching($criteria);

        self::assertCount(10, $tags);
    }
}