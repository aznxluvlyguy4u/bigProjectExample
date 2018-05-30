<?php


namespace AppBundle\Service;


use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Entity\BreedIndexType;
use AppBundle\Entity\BreedIndexTypeRepository;
use AppBundle\Entity\BreedValueGeneticBase;
use AppBundle\Entity\BreedValueGeneticBaseRepository;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\BreedValueTypeRepository;
use AppBundle\Setting\BreedGradingSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class BreedValueService
 * @package AppBundle\Service
 */
class BreedValueService
{

    /** @var Connection */
    private $conn;
    /** @var EntityManagerInterface */
    private $em;
    /** @var Logger */
    private $logger;

    /** @var BreedIndexTypeRepository */
    private $breedIndexTypeRepository;
    /** @var BreedValueTypeRepository */
    private $breedValueTypeRepository;
    /** @var BreedValueGeneticBaseRepository */
    private $breedValueGeneticBaseRepository;
    /** @var array */
    private $breedValueTypesById;

    /**
     * BreedValueService constructor.
     * @param EntityManagerInterface $em
     * @param Logger $logger
     */
    public function __construct(EntityManagerInterface $em, $logger = null)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;

        $this->breedIndexTypeRepository = $this->em->getRepository(BreedIndexType::class);
        $this->breedValueTypeRepository = $this->em->getRepository(BreedValueType::class);
        $this->breedValueGeneticBaseRepository = $this->em->getRepository(BreedValueGeneticBase::class);

        $this->resetSearchArrays();
    }


    /**
     * @return bool
     */
    public function initialize()
    {
        $updatedAnyBreedValueType = $this->initializeBreedValueType();

        return $updatedAnyBreedValueType;
    }


    public function resetSearchArrays()
    {
        $this->resetBreedValueTypesByIdSearchArray();
    }


    public function resetBreedValueTypesByIdSearchArray()
    {
        $this->breedValueTypesById = [];
        /** @var BreedValueType $breedValueType */
        foreach ($this->breedValueTypeRepository->findAll() as $breedValueType) {
            $this->breedValueTypesById[$breedValueType->getId()] = $breedValueType;
        }
    }


    /**
     * @return bool
     */
    public function initializeBreedValueType()
    {
        $breedValueTypeEntities = $this->breedValueTypeRepository->findAll();
        $searchArray = [];

        /** @var BreedValueType $breedValueTypeEntity */
        foreach ($breedValueTypeEntities as $breedValueTypeEntity) {
            $searchArray[$breedValueTypeEntity->getEn()] = $breedValueTypeEntity->getNl();
        }

        $breedValueTypeValues = BreedValueTypeConstant::getConstants();
        $newCount = 0;
        foreach ($breedValueTypeValues as $english => $dutch) {
            if(!key_exists($english, $searchArray)) {
                $breedValueType = new BreedValueType($english, $dutch);
                $this->em->persist($breedValueType);
                $newCount++;
            }
        }

        if($newCount > 0) {
            $this->em->flush();
            $this->logger->notice($newCount . ' new BreedValueType records persisted');
            $this->resetBreedValueTypesByIdSearchArray();

        } else {
            $this->logger->notice('No new BreedValueType records persisted');
        }

        return $newCount > 0;
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public function setMinReliabilityForAllBreedValueTypesByAccuracyOption(CommandUtil $cmdUtil)
    {
        $defaultAccuracy = sqrt(BreedGradingSetting::MIN_RELIABILITY_FOR_GENETIC_BASE) * 100;

        do {
            $accuracy = $cmdUtil->generateQuestion('Set the minimum accuracy (in %) for all breedValueTypes (default = '.$defaultAccuracy.'%)', $defaultAccuracy);
        } while (!ctype_digit($accuracy) && !is_int($accuracy));

        $reliability = pow($accuracy / 100, 2);
        $cmdUtil->writelnWithTimestamp('Chosen accuracy: '.$accuracy.'% = Reliabilty: '.$reliability.' to be persisted');
        if (!$cmdUtil->generateConfirmationQuestion('Is this correct? (y/n, default = n)') ) {
            $cmdUtil->writelnWithTimestamp('Minimum reliability edit aborted');
            return 0;
        }

        $sql = "UPDATE breed_value_type SET min_reliability = $reliability WHERE min_reliability <> $reliability OR min_reliability ISNULL";
        $updateCount = SqlUtil::updateWithCount($this->conn, $sql);

        $count = $updateCount === 0 ? 'No' : $updateCount;
        $cmdUtil->writelnWithTimestamp($count. ' breedValueType records updated with min_reliability = '.$reliability.' (accuracy = '.$accuracy.'%)');
        return $updateCount;
    }


    /**
     * @return int
     * @throws \Exception
     */
    public function initializeBlankGeneticBases()
    {
        $sql = "SELECT t.id as breed_value_type_id, t.nl as dutch_breed_value_name, measurement_year
                  FROM breed_value_type t
                  INNER JOIN (
                      SELECT type_id, DATE_PART('year', generation_date) as measurement_year
                      FROM breed_value
                      GROUP BY type_id, DATE_PART('year', generation_date)
                      )b ON b.type_id = t.id
                  LEFT JOIN breed_value_genetic_base g ON t.id = g.breed_value_type_id
                    AND g.year = b.measurement_year
                WHERE g.id ISNULL";
        $blankGeneticBasesResults = $this->conn->query($sql)->fetchAll();

        $updateCount = 0;
        $blankGeneticBases = [];
        foreach ($blankGeneticBasesResults as $result) {
            $typeId = $result['breed_value_type_id'];
            $measurementYear = $result['measurement_year'];
            $dutchBreedValueName = $result['dutch_breed_value_name'];

            $geneticBaseValue = $this->calculateGeneticBase($measurementYear, $dutchBreedValueName);

            /** @var BreedValueType $breedValueType */
            $breedValueType = ArrayUtil::get($typeId, $this->breedValueTypesById);

            $breedValueGeneticBase = (new BreedValueGeneticBase())
                ->setBreedValueType($breedValueType)
                ->setValue($geneticBaseValue)
                ->setYear($measurementYear)
                ->setOffsetYears(BreedGradingSetting::GENETIC_BASE_YEAR_OFFSET);

            if($geneticBaseValue == null) {
                $blankGeneticBases[] = $breedValueGeneticBase;
                continue;
            }

            $this->em->persist($breedValueGeneticBase);
            $updateCount++;
        }

        if($updateCount > 0) {
            $this->em->flush();
        }

        if(count($blankGeneticBases) > 0) {
            $message = 'For the following BreedValue-Measurement year pairs, no genetic base could be calculated: ';
            $prefix = '';
            /** @var BreedValueGeneticBase $breedValueGeneticBase */
            foreach ($blankGeneticBases as $breedValueGeneticBase) {
                $message = $message . $prefix . $breedValueGeneticBase->getBreedValueTypeEn()
                    . ' ' . $breedValueGeneticBase->getYear();
            }
            throw new \Exception($message, 500);
        }

        if($updateCount > 0) {
            $this->logger->notice($updateCount . ' genetic bases inserted');
        } else {
            $this->logger->notice('No genetic bases were inserted');
        }

        return $updateCount;
    }



    /**
     * @param int $measurementsYear
     * @param string $dutchBreedValueTypeName
     * @return float|null
     */
    public function calculateGeneticBase($measurementsYear, $dutchBreedValueTypeName)
    {
        $geneticBaseYear = $measurementsYear - BreedGradingSetting::GENETIC_BASE_YEAR_OFFSET;

        $sql = "SELECT EXTRACT(YEAR FROM date_of_birth), AVG(b.value) as average
                FROM breed_value b
                  INNER JOIN breed_value_type t ON b.type_id = t.id
                  INNER JOIN animal a ON b.animal_id = a.id
                WHERE EXTRACT(YEAR FROM date_of_birth) = $geneticBaseYear
                      AND b.reliability >= t.min_reliability
                      AND t.nl = '$dutchBreedValueTypeName'
                GROUP BY EXTRACT(YEAR FROM date_of_birth)";
        $results = $this->conn->query($sql)->fetch();

        if(count($results) == 0) { return null; }

        return $results['average'];
    }


    /**
     * @return int
     */
    public function initializeCustomBreedValueTypeSettings()
    {
        /** @var BreedValueType $siGa */
        $siGa = $this->em->getRepository(BreedValueType::class)
            ->findOneByNl('SIgA');

        $updateCount = 0;
        if (!$siGa->isUseNormalDistribution()) {
            $siGa->setUseNormalDistribution(true);
            $this->em->persist($siGa);
            $updateCount++;
        }

        if ($siGa->getStandardDeviationStepSize() === null) {
            $siGa->setStandardDeviationStepSize(10);
            $this->em->persist($siGa);
            $updateCount++;
        }


        /** @var BreedValueType $nziGa */
        $nziGa = $this->em->getRepository(BreedValueType::class)
            ->findOneByNl('NZIgA');
        if ($nziGa->isShowResult()) {
            $nziGa->setShowResult(false);
            $this->em->persist($nziGa);
            $updateCount++;
        }

        /** @var BreedValueType $nziGa */
        $lnFec = $this->em->getRepository(BreedValueType::class)
            ->findOneByNl('LnFEC');
        if ($lnFec->isShowResult()) {
            $lnFec->setShowResult(false);
            $this->em->persist($lnFec);
            $updateCount++;
        }

        if ($updateCount > 0) {
            $this->em->flush();
        }

        $message = ($updateCount > 0 ? $updateCount : 'No') . ' custom breedValueType settings were initialized';

        $this->logger->notice($message);

        return $updateCount;
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function initializeGraphOrdinalData()
    {
        $values = [
            BreedValueTypeConstant::TOTAL_BORN => 1,
            BreedValueTypeConstant::STILL_BORN => 2,
            BreedValueTypeConstant::EARLY_FERTILITY => 3,
            BreedValueTypeConstant::BIRTH_INTERVAL => 4,
            BreedValueTypeConstant::BIRTH_DELIVERY_PROGRESS => 5,
            BreedValueTypeConstant::BIRTH_WEIGHT => 6,
            BreedValueTypeConstant::BIRTH_PROGRESS => 7,
            BreedValueTypeConstant::WEIGHT_AT_8_WEEKS => 8,
            BreedValueTypeConstant::WEIGHT_AT_20_WEEKS => 9,
            BreedValueTypeConstant::MUSCLE_THICKNESS => 10,
            BreedValueTypeConstant::FAT_THICKNESS_3 => 11,
            BreedValueTypeConstant::MUSCULARITY_VG_V => 12,
            BreedValueTypeConstant::MUSCULARITY_VG_M => 12,
            BreedValueTypeConstant::MUSCULARITY_DF => 12,
            BreedValueTypeConstant::PROPORTION_VG_M => 13,
            BreedValueTypeConstant::PROPORTION_DF => 13,
            BreedValueTypeConstant::SKULL_VG_M => 14,
            BreedValueTypeConstant::SKULL_DF => 14,
            BreedValueTypeConstant::EXTERIOR_TYPE_DF => 15,
            BreedValueTypeConstant::EXTERIOR_TYPE_VG_M => 15,
            BreedValueTypeConstant::LEG_WORK_VG_M => 16,
            BreedValueTypeConstant::LEG_WORK_DF => 16,
            BreedValueTypeConstant::PROGRESS_VG_M => 17,
            BreedValueTypeConstant::PROGRESS_DF => 17,
            BreedValueTypeConstant::ODIN_BC => 18,
            BreedValueTypeConstant::TAIL_LENGTH => 19,
        ];

        $setsAsString = '';
        $prefix = '';
        foreach ($values as $breedValue => $ordinal) {
            $setsAsString .= $prefix."('".$breedValue."',".$ordinal.")";
            $prefix = ',';
        }

        $sql = "UPDATE breed_value_type SET graph_ordinal = v.graph_ordinal FROM (
                SELECT * FROM (VALUES $setsAsString) AS v(nl, graph_ordinal)
                ) AS v(nl, graph_ordinal) WHERE breed_value_type.nl = v.nl";

        $updateCount = SqlUtil::updateWithCount($this->conn, $sql);

        $message = ($updateCount > 0 ? $updateCount : 'No') . ' breedValueType graph ordinals were set';

        $this->logger->notice($message);

        return $updateCount;
    }
}