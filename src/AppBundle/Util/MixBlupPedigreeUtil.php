<?php

namespace AppBundle\Util;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\MixBlup\ExteriorDataFile;
use AppBundle\MixBlup\LambMeatIndexDataFile;
use AppBundle\MixBlup\MixBlupInstructionFileBase;
use AppBundle\MixBlup\ReproductionDataFile;
use Doctrine\DBAL\Connection;

/**
 * Class MixBlupPedigreeUtil
 * @package AppBundle\Util
 */
class MixBlupPedigreeUtil
{
    /** @var Connection */
    private $conn;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var array */
    private $animalIdsToInclude;

    /** @var array */
    private $femalePedigreeSearchArray;

    /** @var array */
    private $malePedigreeSearchArray;

    /** @var string */
    private $initializedAnimalIdsFilterArrayType;

    /**
     * MixBlupPedigreeUtil constructor.
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     */
    public function __construct(Connection $conn, $cmdUtil = null)
    {
        $this->conn = $conn;
        $this->cmdUtil = $cmdUtil;
        $this->animalIdsToInclude = [];
        $this->initializedAnimalIdsFilterArrayType = null;

        $this->initialize();
    }


    public function initialize()
    {
        $this->writeln('Initialize search arrays ...');
        $this->setupPedigreeSearchArrays();
    }


    /**
     * @param string $mixBlupType
     */
    private function filterAnimalIds($mixBlupType)
    {
        if($this->initializedAnimalIdsFilterArrayType != $mixBlupType) {
            $this->writeln('Generating animalIdsToInclude filter array...');
            $this->setupFilteredAnimalIds($mixBlupType);
            $this->initializedAnimalIdsFilterArrayType = $mixBlupType;
        }
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setupPedigreeSearchArrays()
    {
        $this->writeln('SQL retrieve data ...');
        $sql = "SELECT id as ".JsonInputConstant::ANIMAL_ID.", parent_father_id, parent_mother_id, type
                FROM animal
                WHERE type <> 'Neuter'";
        $fullPedigreeData = $this->conn->query($sql)->fetchAll();

        $this->writeln('Creating pedigree search arrays ...');
        foreach ($fullPedigreeData as $pedigreeRecord)
        {
            $animalId = $pedigreeRecord[JsonInputConstant::ANIMAL_ID];
            $fatherId = $pedigreeRecord['parent_father_id'];
            $motherId = $pedigreeRecord['parent_mother_id'];
            $type = $pedigreeRecord['type'];
            if($type == AnimalObjectType::Ram) {
                $this->malePedigreeSearchArray[$animalId] = $pedigreeRecord;
            } elseif($type == AnimalObjectType::Ewe) {
                $this->femalePedigreeSearchArray[$animalId] = $pedigreeRecord;
            }
        }
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setupFilteredAnimalIds($mixBlupType)
    {
        $this->writeln('Make '.$mixBlupType.' filter query for Animals ...');
        
        switch ($mixBlupType) {
            case MixBlupType::EXTERIOR:
                $sql = ExteriorDataFile::getSqlQueryRelatedAnimals();
                break;
            case MixBlupType::FERTILITY:
                $sql = ReproductionDataFile::getSqlQueryRelatedAnimals();
                break;
            case MixBlupType::LAMB_MEAT_INDEX:
                $sql = LambMeatIndexDataFile::getSqlQueryRelatedAnimals();
                break;
            default:
                throw new \Exception('Unsupported MixBlupType fro filtered animalIds');
                break;
        }
        
        
        $childIdResults = $this->conn->query($sql)->fetchAll();
        $this->writeln('Filter animalIds ...');

        $loopCount = 0;
        $totalCount = count($childIdResults);
        if($this->cmdUtil) { $this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1); }

        foreach ($childIdResults as $childIdResult)
        {
            $this->includeAnimalIdByDataArray($childIdResult);

            if($this->cmdUtil) { $this->cmdUtil->advanceProgressBar(1,'Loop: '.$loopCount++.' | '.count($this->animalIdsToInclude). ' animalIds included'); }
        }

        //TODO include childId sets from other BreedValue datasets

        if($this->cmdUtil) { $this->cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }


    /**
     * @param mixed|null $animalDataArray
     * @return null
     */
    private function includeAnimalIdByDataArray($animalDataArray)
    {
        if($animalDataArray == null) { return null; }

        $animalId = $animalDataArray[JsonInputConstant::ANIMAL_ID];
        if($animalId != null) {

            if(key_exists($animalId, $this->animalIdsToInclude)) { return; }
            $this->animalIdsToInclude[$animalId] = $animalId;
            $type = $animalDataArray[JsonInputConstant::TYPE];

            $animalDataArray = null;
            if($type == AnimalObjectType::Ram) {
                $animalDataArray = ArrayUtil::get($animalId, $this->malePedigreeSearchArray);
            } elseif($type == AnimalObjectType::Ewe) {
                $animalDataArray = ArrayUtil::get($animalId, $this->femalePedigreeSearchArray);
            }

            if($animalDataArray) {
                $fatherId = $animalDataArray['parent_father_id'];
                if($fatherId) {
                    if(key_exists($fatherId, $this->animalIdsToInclude)) { return; }
                    $fatherDataArray = ArrayUtil::get($fatherId, $this->malePedigreeSearchArray);
                    $this->includeAnimalIdByDataArray($fatherDataArray);
                }
                $motherId = $animalDataArray['parent_mother_id'];
                if($motherId) {
                    if(key_exists($motherId, $this->animalIdsToInclude)) { return; }
                    $motherDataArray = ArrayUtil::get($motherId, $this->femalePedigreeSearchArray);
                    $this->includeAnimalIdByDataArray($motherDataArray);
                }
            }
        }
    }


    /**
     * @param boolean $print
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getFullSet($print = true)
    {
        if($print) { $this->writeln('Get Pedigree DATA by full sql query...'); }
        return $this->conn->query($this->getSqlQuery())->fetchAll();
    }


    /**
     * @return array
     */
    public function getExteriorOptimizedSet()
    {
        return $this->getOptimizedSet(MixBlupType::EXTERIOR);
    }


    /**
     * @return array
     */
    public function getFertilityOptimizedSet()
    {
        return $this->getOptimizedSet(MixBlupType::FERTILITY);
    }


    /**
     * @return array
     */
    public function getLambMeatIndexOptimizedSet()
    {
        return $this->getOptimizedSet(MixBlupType::LAMB_MEAT_INDEX);
    }


    /**
     * @return array
     */
    public function getWormOptimizedSet()
    {
        return $this->getOptimizedSet(MixBlupType::WORM);
    }


    /**
     * @param string $mixBlupType
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getOptimizedSet($mixBlupType)
    {
        $this->filterAnimalIds($mixBlupType);
        $this->writeln('Get '.$mixBlupType.' Pedigree DATA by optimized sql query...');
        $filteredResults = [];

        /*
         * Instead of filtering a huge amount of ids directly in the sql query,
         * it is much quicker to just retrieve the whole sets and filter them in memory.
         */
        foreach ($this->getFullSet(false) as $record) {
            $animalId = $record[JsonInputConstant::ANIMAL_ID];
            if(key_exists($animalId, $this->animalIdsToInclude)) {
                $filteredResults[$animalId] = $record;
            }
        }
        return $filteredResults;
    }


    /**
     * @return string
     */
    private function getSqlQuery()
    {
        $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT;

        return "SELECT
                  a.id as ".JsonInputConstant::ANIMAL_ID.",
                  CONCAT(a.uln_country_code, a.uln_number) AS ".JsonInputConstant::ULN.",
                  COALESCE(NULLIF(CONCAT(f.uln_country_code, f.uln_number),''), '".$nullReplacement."') AS ".JsonInputConstant::ULN_FATHER.",
                  COALESCE(NULLIF(CONCAT(m.uln_country_code, m.uln_number),''), '".$nullReplacement."') AS ".JsonInputConstant::ULN_MOTHER.",
                  a.ubn_of_birth AS ".JsonInputConstant::UBN_OF_BIRTH."
                FROM animal a
                  LEFT JOIN animal f ON f.id = a.parent_father_id
                  LEFT JOIN animal m ON m.id = a.parent_mother_id
                WHERE a.type <> 'Neuter'";
    }


    private function writeln($string)
    {
        if($this->cmdUtil) {
            $this->cmdUtil->writeln($string);
        }
    }
}