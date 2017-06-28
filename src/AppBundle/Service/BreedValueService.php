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
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class BreedValueService
 * @package AppBundle\Service
 */
class BreedValueService
{

    /** @var Connection */
    private $conn;
    /** @var ObjectManager */
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
     * @param ObjectManager $em
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, $logger = null)
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
            $this->logger->notice($updateCount . ' genetic bases updated');
        } else {
            $this->logger->notice('No genetic bases were updated');
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

}