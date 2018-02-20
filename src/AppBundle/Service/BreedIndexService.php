<?php


namespace AppBundle\Service;


use AppBundle\Constant\BreedIndexDiscriminatorTypeConstant;
use AppBundle\Constant\BreedIndexTypeConstant;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Entity\BreedIndexType;
use AppBundle\Entity\BreedIndexTypeRepository;
use AppBundle\Entity\BreedValueGeneticBase;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\BreedValueTypeRepository;
use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class BreedIndexService
 * @package AppBundle\Service
 */
class BreedIndexService
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

    /**
     * BreedIndexService constructor.
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
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->em;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return BreedIndexTypeRepository
     */
    public function getBreedIndexTypeRepository()
    {
        return $this->breedIndexTypeRepository;
    }

    /**
     * @return BreedValueTypeRepository
     */
    public function getBreedValueTypeRepository()
    {
        return $this->breedValueTypeRepository;
    }

    /**
     * @return bool
     */
    public function initialize()
    {
        $updatedAnyBreedIndexType = $this->initializeBreedIndexType();
        return $updatedAnyBreedIndexType;
    }


    /**
     * @return bool
     */
    public function initializeBreedIndexType()
    {
        $breedIndexTypeEntities = $this->breedIndexTypeRepository->findAll();
        $searchArray = [];

        /** @var BreedIndexType $breedIndexTypeEntity */
        foreach ($breedIndexTypeEntities as $breedIndexTypeEntity) {
            $searchArray[$breedIndexTypeEntity->getEn()] = $breedIndexTypeEntity->getNl();
        }

        $breedIndexTypeValues = BreedIndexTypeConstant::getConstants();
        $newCount = 0;
        foreach ($breedIndexTypeValues as $english => $dutch) {
            if(!key_exists($english, $searchArray)) {
                $breedIndexType = new BreedIndexType($english, $dutch);
                $this->em->persist($breedIndexType);
                $newCount++;
            }
        }

        if($newCount > 0) {
            $this->em->flush();
            $this->logger->notice($newCount . ' new BreedIndexType records persisted');
        } else {
            $this->logger->notice('No new BreedIndexType records persisted');
        }

        return $newCount > 0;
    }


    public function updateWormResistanceIndexes($generationDateSqlString)
    {
        // TODO
    }


    /**
     * @param string $generationDateSqlString
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function updateLambMeatIndexes($generationDateSqlString)
    {
        // Validate if geneticBase is not null for given generationDate
        if (!$this->areLambMeatIndexGeneticBasesNotNull($generationDateSqlString)) {
            $errorMessage = 'GeneticBases are missing for lambMeatIndex year '.mb_substr($generationDateSqlString, 0, 4);
            $this->getLogger()->error($errorMessage);
            throw new \Exception($errorMessage);

        } else {
            if ($this->insertEmptyLambMeatIndexes($generationDateSqlString) > 0) {
                $this->fixIncongruentBreedIndexTables();
            }
            $this->updateIncongruentLambMeatIndexes($generationDateSqlString);
        }
    }


    /**
     * @param int|string|\DateTime $generationDateSqlString
     * @return bool
     */
    private function areLambMeatIndexGeneticBasesNotNull($generationDateSqlString)
    {
        $year = null;
        if ($generationDateSqlString instanceof \DateTime) {
            $year = $generationDateSqlString->format('Y');
        } elseif(is_string($generationDateSqlString)) {
            $year = mb_substr($generationDateSqlString, 0, 4);
        } else {
            return false;
        }

        $breedValueTypes = $this->getManager()->getRepository(BreedValueGeneticBase::class)
            ->getLambMeatIndexBasesByYear($year);

        $containsBreedValueTypes = [
            BreedValueTypeConstant::GROWTH => false,
            BreedValueTypeConstant::MUSCLE_THICKNESS => false,
            BreedValueTypeConstant::FAT_THICKNESS_3 => false,
        ];

        /** @var BreedValueGeneticBase $breedValueGeneticBase */
        foreach ($breedValueTypes as $breedValueGeneticBase) {
            foreach ($containsBreedValueTypes as $breedValueTypeNl => $containsBreedValueType) {
                if ($breedValueGeneticBase->getBreedValueType() && $breedValueGeneticBase->getBreedValueType()->getNl() === $breedValueTypeNl) {
                    $containsBreedValueTypes[$breedValueTypeNl] = true;
                }
            }
        }

        return !in_array(false, $containsBreedValueTypes);
    }


    /**
     * @param null|string $generationDateSqlString
     * @throws \Doctrine\DBAL\DBALException
     */
    private function insertEmptyLambMeatIndexes($generationDateSqlString)
    {
        $sql = "INSERT INTO breed_index (animal_id, log_date, generation_date, index, accuracy, type)
                (
                  ".$this->incongruentLambMeatIndexTypeQuery($generationDateSqlString, true)."
                )";
        $insertCount = SqlUtil::updateWithCount($this->getConnection(), $sql);

        if ($insertCount > 0) {
            $this->fixIncongruentBreedIndexTables();
        }

        $insertCountText = $insertCount === 0 ? 'No' : $insertCount;
        $generationDateText = $generationDateSqlString !== null ? ' for generationDate '.$generationDateSqlString : '';

        $this->getLogger()->notice($insertCountText . ' '.BreedIndexDiscriminatorTypeConstant::LAMB_MEAT.' indexes where inserted'.$generationDateText);

        return $insertCount;
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fixIncongruentBreedIndexTables()
    {
        $sql = "INSERT INTO lamb_meat_breed_index (id) (
                  SELECT
                  breed_index.id
                  FROM breed_index
                  LEFT JOIN lamb_meat_breed_index lmbi ON breed_index.id = lmbi.id
                                                          WHERE lmbi.id ISNULL
                )";
        $insertCount = SqlUtil::updateWithCount($this->getConnection(), $sql);

        $insertCountText = $insertCount === 0 ? 'No' : $insertCount;
        $this->getLogger()->notice($insertCountText . ' '.BreedIndexDiscriminatorTypeConstant::LAMB_MEAT.' child table records where inserted');

        return $insertCount;
    }


    /**
     * @param null|string $generationDateSqlString
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateIncongruentLambMeatIndexes($generationDateSqlString)
    {
        $sql = "UPDATE breed_index SET index = calc.calculated_breed_index, accuracy = calc.calculated_breed_index_accuracy
                FROM (
                                  ".$this->incongruentLambMeatIndexTypeQuery($generationDateSqlString, false)."
                                ) AS calc(breed_index_id, animal_id, now, generation_date, calculated_breed_index, calculated_breed_index_accuracy, discriminator_type)
                WHERE calc.breed_index_id = breed_index.id";
        $updateCount = SqlUtil::updateWithCount($this->getConnection(), $sql);

        $insertCountText = $updateCount === 0 ? 'No' : $updateCount;
        $generationDateText = $generationDateSqlString !== null ? ' for generationDate '.$generationDateSqlString : '';

        $this->getLogger()->notice($insertCountText . ' '.BreedIndexDiscriminatorTypeConstant::LAMB_MEAT.' indexes where updated'.$generationDateText);

        return $updateCount;
    }


    /**
     * @param null|string $generationDateSqlString in a format like this 2016-10-04 00:00:00
     * @param boolean $isForInsert
     * @return string
     */
    private function incongruentLambMeatIndexTypeQuery($generationDateSqlString = null, $isForInsert)
    {
        $generationDateFilter1 = '';
        $generationDateFilter2 = '';
        if ($generationDateSqlString) {
            $generationDateFilter1 = "AND b.generation_date = '".$generationDateSqlString."'";
            $generationDateFilter2 = "AND x.generation_date = '".$generationDateSqlString."'";
        }

        if ($isForInsert) {
            $breedIndexIdSelect = ' ';
            $isCurrentValueDifferentFilter = "                  
                  (
                     -- Current breed index is empty
                      breed_index.index ISNULL
                       OR
                     -- Current breed index accuracy is empty
                      breed_index.accuracy ISNULL
                  )";
        } else {
            $breedIndexIdSelect = 'breed_index.id as breed_index_id,';
            $isCurrentValueDifferentFilter = "                  
                  (
                    -- Current breed index does not match calculated value
                    (breed_index.index <>
                     growth_coefficients.c * (growth.value - growth_genetic_base.value)
                     + fat_thickness_coefficients.c * (fat_thickness.value - fat_thickness_genetic_base.value)
                     + muscle_thickness_coefficients.c * (muscle_thickness.value - muscle_thickness_genetic_base.value))
                       OR
                     -- Current breed index accuracy does not match calculated value
                     (breed_index.accuracy <>
                     sqrt(1 - (
                                muscle_thickness_coefficients.t * (1 - muscle_thickness.reliability) +
                                growth_coefficients.t * (1 - growth.reliability) +
                                fat_thickness_coefficients.t * (1 - fat_thickness.reliability)
                              ) / (muscle_thickness_coefficients.t + growth_coefficients.t + fat_thickness_coefficients.t))
                     )
                  )";
        }

        return "SELECT
                --   growth.value as growth_value,
                --   growth.reliability as growth_reliability,
                --   fat_thickness.value as fat_thickness_value,
                --   fat_thickness.reliability as fat_thickness_reliability,
                --   muscle_thickness.value as muscle_thickness,
                --   muscle_thickness.reliability as muscle_thickness_reliability,
                --   growth_coefficients.c as  growth_coefficients_c,
                --   fat_thickness_coefficients.c as  fat_thickness_coefficients_c,
                --   muscle_thickness_coefficients.c as  muscle_thickness_coefficients_c,
                --   growth_genetic_base.value as growth_genetic_base_value,
                --   fat_thickness_genetic_base.value as fat_thickness_genetic_base_value,
                --   muscle_thickness_genetic_base.value as muscle_thickness_genetic_base_value,
                --   breed_index.index as current_breed_index,
                --   breed_index.accuracy as current_breed_index_accuracy,
                  ".$breedIndexIdSelect." 
                  x.animal_id,
                  NOW(),
                  x.generation_date,
                  growth_coefficients.c * (growth.value - growth_genetic_base.value)
                  + fat_thickness_coefficients.c * (fat_thickness.value - fat_thickness_genetic_base.value)
                  + muscle_thickness_coefficients.c * (muscle_thickness.value -  muscle_thickness_genetic_base.value) as calculated_breed_index,
                  sqrt(1-(
                           muscle_thickness_coefficients.t*(1-muscle_thickness.reliability) +
                           growth_coefficients.t*(1-growth.reliability) +
                           fat_thickness_coefficients.t*(1-fat_thickness.reliability)
                         )/(muscle_thickness_coefficients.t + growth_coefficients.t + fat_thickness_coefficients.t)) as calculated_breed_index_accuracy,
                  '".BreedIndexDiscriminatorTypeConstant::LAMB_MEAT."' as discriminator_type
                FROM (
                    SELECT
                      generation_date, animal_id, COUNT(*) as count
                    FROM breed_value b
                    WHERE b.type_id IN (
                      SELECT id FROM breed_value_type
                      WHERE breed_value_type.nl = '".BreedValueTypeConstant::GROWTH."'
                            OR breed_value_type.nl = '".BreedValueTypeConstant::FAT_THICKNESS_3."'
                            OR breed_value_type.nl = '".BreedValueTypeConstant::MUSCLE_THICKNESS."'
                    )
                    " .$generationDateFilter1 . " 
                    GROUP BY generation_date, animal_id
                    )x
                  INNER JOIN breed_value growth ON growth.animal_id = x.animal_id AND growth.generation_date = x.generation_date
                  INNER JOIN breed_value fat_thickness ON fat_thickness.animal_id = x.animal_id AND fat_thickness.generation_date = x.generation_date
                  INNER JOIN breed_value muscle_thickness ON muscle_thickness.animal_id = x.animal_id AND muscle_thickness.generation_date = x.generation_date
                  INNER JOIN breed_value_type growth_type ON growth.type_id = growth_type.id
                  INNER JOIN breed_value_type fat_thickness_type ON fat_thickness.type_id = fat_thickness_type.id
                  INNER JOIN breed_value_type muscle_thickness_type ON muscle_thickness.type_id = muscle_thickness_type.id
                  LEFT JOIN (
                      -- LATEST breed_index of animal_id-generation_date combination
                              SELECT breed_index.*
                              FROM breed_index
                                INNER JOIN (
                                  SELECT
                                    generation_date, animal_id, MAX(id) as max_id
                                  FROM breed_index
                                  GROUP BY generation_date, animal_id
                                )y ON y.max_id = breed_index.id
                      )breed_index ON breed_index.generation_date = x.generation_date AND breed_index.animal_id = x.animal_id
                  LEFT JOIN (
                      -- IT IS A PREREQUISITE THAT ONLY ONLY ACTIVE BREED INDEX COEFFICIENT EXISTS FOR EACH BREED_INDEX_TYPE-BREED_VALUE_TYPE COMBINATION
                      SELECT breed_index_coefficient.* FROM breed_index_coefficient
                        INNER JOIN breed_index_type ON breed_index_coefficient.breed_index_type_id = breed_index_type.id
                        INNER JOIN breed_value_type growth_type ON breed_index_coefficient.breed_value_type_id = growth_type.id
                      WHERE breed_index_type.nl = '".BreedIndexTypeConstant::LAMB_MEAT_INDEX."'
                            AND growth_type.nl = '".BreedValueTypeConstant::GROWTH."'
                            AND breed_index_coefficient.end_date ISNULL
                  )growth_coefficients ON growth_type.id = growth_coefficients.breed_value_type_id
                  LEFT JOIN (
                              -- IT IS A PREREQUISITE THAT ONLY ONLY ACTIVE BREED INDEX COEFFICIENT EXISTS FOR EACH BREED_INDEX_TYPE-BREED_VALUE_TYPE COMBINATION
                              SELECT breed_index_coefficient.* FROM breed_index_coefficient
                                INNER JOIN breed_index_type ON breed_index_coefficient.breed_index_type_id = breed_index_type.id
                                INNER JOIN breed_value_type fat_thickness_type ON breed_index_coefficient.breed_value_type_id = fat_thickness_type.id
                              WHERE breed_index_type.nl = '".BreedIndexTypeConstant::LAMB_MEAT_INDEX."'
                                    AND fat_thickness_type.nl = '".BreedValueTypeConstant::FAT_THICKNESS_3."'
                                    AND breed_index_coefficient.end_date ISNULL
                            )fat_thickness_coefficients ON fat_thickness_type.id = fat_thickness_coefficients.breed_value_type_id
                  LEFT JOIN (
                              -- IT IS A PREREQUISITE THAT ONLY ONLY ACTIVE BREED INDEX COEFFICIENT EXISTS FOR EACH BREED_INDEX_TYPE-BREED_VALUE_TYPE COMBINATION
                              SELECT breed_index_coefficient.* FROM breed_index_coefficient
                                INNER JOIN breed_index_type ON breed_index_coefficient.breed_index_type_id = breed_index_type.id
                                INNER JOIN breed_value_type muscle_thickness_type ON breed_index_coefficient.breed_value_type_id = muscle_thickness_type.id
                              WHERE breed_index_type.nl = '".BreedIndexTypeConstant::LAMB_MEAT_INDEX."'
                                    AND muscle_thickness_type.nl = '".BreedValueTypeConstant::MUSCLE_THICKNESS."'
                                    AND breed_index_coefficient.end_date ISNULL
                            )muscle_thickness_coefficients ON muscle_thickness_type.id = muscle_thickness_coefficients.breed_value_type_id
                  LEFT JOIN breed_value_genetic_base growth_genetic_base ON growth_type.id = growth_genetic_base.breed_value_type_id AND DATE_PART('year', x.generation_date) = growth_genetic_base.year
                  LEFT JOIN breed_value_genetic_base fat_thickness_genetic_base ON fat_thickness_type.id = fat_thickness_genetic_base.breed_value_type_id AND DATE_PART('year', x.generation_date) = fat_thickness_genetic_base.year
                  LEFT JOIN breed_value_genetic_base muscle_thickness_genetic_base ON muscle_thickness_type.id = muscle_thickness_genetic_base.breed_value_type_id AND DATE_PART('year', x.generation_date) = muscle_thickness_genetic_base.year
                WHERE
                  growth_type.nl = '".BreedValueTypeConstant::GROWTH."'
                  AND fat_thickness_type.nl = '".BreedValueTypeConstant::FAT_THICKNESS_3."'
                  AND muscle_thickness_type.nl = '".BreedValueTypeConstant::MUSCLE_THICKNESS."'
                  AND growth.reliability >= growth_type.min_reliability
                  AND fat_thickness.reliability >= fat_thickness_type.min_reliability
                  AND muscle_thickness.reliability >= muscle_thickness_type.min_reliability
                    " .$generationDateFilter2 . " 
                  AND
                  ".$isCurrentValueDifferentFilter."
                ";
    }
}