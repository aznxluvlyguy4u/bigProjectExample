<?php


namespace AppBundle\Tests\Criteria;


use AppBundle\Criteria\ExteriorCriteria;
use AppBundle\Entity\Exterior;
use AppBundle\Enumerator\ExteriorKind;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group exterior
 * @group criteria
 */
class ExteriorCriteriaTest extends KernelTestCase
{
    const MARKINGS = 1000.0;

    /** @var EntityManagerInterface|ObjectManager */
    static private $em;

    /** @var int */
    private static $oneYearAge;
    /** @var int */
    private static $twoYearAge;

    /** @var ArrayCollection */
    private $exteriors;

    /**
     * Runs before class setup
     */
    public static function setUpBeforeClass()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        self::$oneYearAge = ExteriorCriteria::ONE_YEAR_AGE_IN_DAYS_LIMIT;
        self::$twoYearAge = ExteriorCriteria::ONE_YEAR_AGE_IN_DAYS_LIMIT + 1;
    }

    /*
     * Runs after all testcases ran and teardown
     */
    public static function tearDownAfterClass()
    {
        self::$oneYearAge = null;
        self::$twoYearAge = null;

        parent::ensureKernelShutdown();
    }

    /**
     * Runs on each testcase
     */
    public function setUp()
    {
        $this->exteriors = new ArrayCollection();
    }

    /**
     * @group bm
     */
    public function testBmSuccess()
    {
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DO_, 75));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::VG_, 76));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DF_, 75));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DD_, 88));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HK_, 99));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HH_, 77));

        $oneYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredBMParentExterior(self::$oneYearAge));
        $twoYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredBMParentExterior(self::$twoYearAge));

        $this->assertCount(2, $oneYearResult, 'BM one year exteriors');
        $this->assertCount(5, $twoYearResult, 'BM two year exteriors');
    }


    /**
     * @group bm
     */
    public function testBmFailure()
    {
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DO_, 76));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::VG_, 74));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DF_, 71));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DD_, 70));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HK_, 75));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HH_, 45));

        $oneYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredBMParentExterior(self::$oneYearAge));
        $twoYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredBMParentExterior(self::$twoYearAge));

        $this->assertCount(1, $oneYearResult, 'BM one year exteriors');
        $this->assertCount(2, $twoYearResult, 'BM two year exteriors');
    }


    /**
     * @group te
     */
    public function testTEFatherSuccess()
    {
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DO_, 70));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::VG_, 71));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DF_, 75));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DD_, 70));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HK_, 99));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HH_, 77));

        $oneYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredTEFatherExterior(self::$oneYearAge));
        $twoYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredTEFatherExterior(self::$twoYearAge));

        $this->assertCount(2, $oneYearResult, 'TE Father one year exteriors');
        $this->assertCount(5, $twoYearResult, 'TE Father two year exteriors');
    }


    /**
     * @group te
     */
    public function testTEFatherFailure()
    {
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DO_, 76));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::VG_, 67));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DF_, 71));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DD_, 64));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HK_, 70));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HH_, 45));

        $oneYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredTEFatherExterior(self::$oneYearAge));
        $twoYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredTEFatherExterior(self::$twoYearAge));

        $this->assertCount(1, $oneYearResult, 'TE Father one year exteriors');
        $this->assertCount(3, $twoYearResult, 'TE Father two year exteriors');
    }


    /**
     * @group te
     */
    public function testTEMotherOfRamSuccess()
    {
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DO_, 75, 77));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::VG_, 70, 66));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DF_, 75, 77));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DD_, 88, 78));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HK_, 99, 77));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HH_, 77, 99));

        $oneYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredTEMotherOfRamExterior(self::$oneYearAge));
        $twoYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredTEMotherOfRamExterior(self::$twoYearAge));

        $this->assertCount(1, $oneYearResult, 'TE Mother of Ram one year exteriors');
        $this->assertCount(4, $twoYearResult, 'TE Mother of Ram two year exteriors');
    }


    /**
     * @group te
     */
    public function testTEMotherOfRamFailure()
    {
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DO_, 70, 99));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::VG_, 71, 99));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DF_, 89, 84));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DD_, 64, 88));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HK_, 99, 66));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HH_, 75, 77));

        $oneYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredTEMotherOfRamExterior(self::$oneYearAge));
        $twoYearResult = $this->exteriors->matching(ExteriorCriteria::pureBredTEMotherOfRamExterior(self::$twoYearAge));

        $this->assertCount(1, $oneYearResult, 'TE Mother of Ram one year exteriors');
        $this->assertCount(2, $twoYearResult, 'TE Mother of Ram two year exteriors');
    }


    /**
     * @group te
     */
    public function testTEMotherOfEweSuccess()
    {
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DO_, 80));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::VG_, 99));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DF_, 87));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DD_, 88));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HK_, 83));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HH_, 80));

        $result = $this->exteriors->matching(ExteriorCriteria::pureBredTEMotherOfEweExterior());

        $this->assertCount(5, $result, 'TE Mother of Ewe one year exteriors');
    }


    /**
     * @group te
     */
    public function testTEMotherOfEweFailure()
    {
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DO_, 81));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::VG_, 99));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DF_, 89));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::DD_, 64));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HK_, 99));
        $this->exteriors->add(ExteriorCriteriaTest::exterior(ExteriorKind::HH_, 75));

        $result = $this->exteriors->matching(ExteriorCriteria::pureBredTEMotherOfEweExterior());

        $this->assertCount(3, $result, 'TE Mother of Ewe one year exteriors');
    }


    /**
     * Runs after each testcase
     */
    public function tearDown()
    {
        $this->exteriors->clear();
        $this->exteriors = null;
    }
    

    /**
     * @param string $kind
     * @param int $generalAppearance
     * @param int $muscularity
     * @return Exterior
     */
    public static function exterior($kind, $generalAppearance, $muscularity = 70)
    {
        return (new Exterior())
            ->setKind($kind)
            ->setGeneralAppearance(floatval($generalAppearance))
            ->setMuscularity(floatval($muscularity))
            ->setSkull(70.0)
            ->setProportion(70.0)
            ->setProgress(70.0)
            ->setExteriorType(70.0)
            ->setLegWork(70.0)
            ->setFur(70.0)
            ->setHeight(70.0)
            ->setBreastDepth(33.0)
            ->setTorsoLength(33.0)
            ->setMarkings(self::MARKINGS)
            ->setIsActive(true)
        ;
    }


}