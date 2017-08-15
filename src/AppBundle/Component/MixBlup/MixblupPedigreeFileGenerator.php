<?php

namespace AppBundle\Component\MixBlup;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvWriterUtil;
use AppBundle\Util\MixBlupPedigreeUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class MixblupPedigreeFileGenerator
 * @package AppBundle\MixBlup
 */
class MixblupPedigreeFileGenerator
{
    
    
    /**
     * @param Connection $conn
     * @param Logger $logger
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    public static function generateExteriorOptimizedSet(Connection $conn, Logger $logger, $cmdUtil = null)
    {
        return self::generateSet($conn, $logger, MixBlupType::EXTERIOR, $cmdUtil);
    }


    /**
     * @param Connection $conn
     * @param Logger $logger
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    public static function generateFertilityOptimizedSet(Connection $conn, Logger $logger, $cmdUtil = null)
    {
        return self::generateSet($conn, $logger, MixBlupType::FERTILITY, $cmdUtil);
    }


    /**
     * @param Connection $conn
     * @param Logger $logger
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    public static function generateLambMeatIndexOptimizedSet(Connection $conn, Logger $logger, $cmdUtil = null)
    {
        return self::generateSet($conn, $logger, MixBlupType::LAMB_MEAT_INDEX, $cmdUtil);
    }


    /**
     * @param Connection $conn
     * @param Logger $logger
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    public static function generateFullSet(Connection $conn, Logger $logger, $cmdUtil = null)
    {
        return self::generateSet($conn, $logger, null, $cmdUtil);
    }
    

    /**
     * @param Connection $conn
     * @param string $getOptimizedSet
     * @param CommandUtil|null $cmdUtil
     * @param Logger $logger
     * @return array
     */
    private static function generateSet(Connection $conn, Logger $logger, $getOptimizedSet = null, $cmdUtil = null)
    {
        $mixBlupPedigreeUtil = new MixBlupPedigreeUtil($conn, $logger, $cmdUtil);

        switch ($getOptimizedSet) {
            case MixBlupType::EXTERIOR:
                $pedigreeRecords = $mixBlupPedigreeUtil->getExteriorOptimizedSet();
                $choice = MixBlupType::EXTERIOR.' optimized';
                break;
            case MixBlupType::FERTILITY:
                $pedigreeRecords = $mixBlupPedigreeUtil->getFertilityOptimizedSet();
                $choice = MixBlupType::FERTILITY.' optimized';
                break;
            case MixBlupType::LAMB_MEAT_INDEX:
                $pedigreeRecords = $mixBlupPedigreeUtil->getLambMeatIndexOptimizedSet();
                $choice = MixBlupType::LAMB_MEAT_INDEX.' optimized';
                break;
            default:
                $pedigreeRecords = $mixBlupPedigreeUtil->getFullSet();
                $choice = 'FULL';
                break;
        }
        if($cmdUtil) {
            $cmdUtil->writeln('Get '.$choice.' pedigree set');   
        }

        $records = [];
        if($cmdUtil) { $cmdUtil->writeln('Formatting Records...'); }
        foreach ($pedigreeRecords as $recordArray)
        {
            $records[] = self::getFormattedPedigreeRecord($recordArray);
        }

        $mixBlupPedigreeUtil = null;
        if($cmdUtil) { $cmdUtil->writeln('Pedigree records generated!'); }
        
        return $records;
    }
    

    /**
     * @param array $recordArray
     * @return string
     */
    private static function getFormattedPedigreeRecord($recordArray)
    {
        return
            self::getFormattedIdFromRecord(JsonInputConstant::ANIMAL_ID, $recordArray).
            self::getFormattedParentIdFromRecord(JsonInputConstant::FATHER_ID, $recordArray).
            self::getFormattedParentIdFromRecord(JsonInputConstant::MOTHER_ID, $recordArray).
            self::getFormattedUbnOfBirthFromRecord($recordArray).
            self::getFormattedGenderFromType($recordArray).
            self::getFormattedDateOfBirthFromRecord($recordArray).
            self::getFormattedUlnFromRecord($recordArray)
            ;
    }


    /**
     * @param $key 'father_id' or 'mother_id'
     * @param $recordArray
     * @return string
     */
    private static function getFormattedParentIdFromRecord($key, $recordArray)
    {
        $excludeKey = $key === JsonInputConstant::FATHER_ID  ? JsonInputConstant::EXCLUDE_FATHER : JsonInputConstant::EXCLUDE_MOTHER;
        $excludeParent = ArrayUtil::get($excludeKey, $recordArray, false);
        if($excludeParent) {
            $recordArray[$key] = MixBlupInstructionFileBase::CONSTANT_MISSING_PARENT_REPLACEMENT;
        }

        return self::getFormattedIdFromRecord($key, $recordArray);
    }


    /**
     * @param string $key
     * @param array $recordArray
     * @return string
     */
    private static function getFormattedIdFromRecord($key, $recordArray)
    {
        return CsvWriterUtil::getFormattedValueFromArray($recordArray, MaxLength::ANIMAL_ID, $key, true, MixBlupInstructionFileBase::CONSTANT_MISSING_PARENT_REPLACEMENT);
    }


    /**
     * @param array $recordArray
     * @return string
     */
    private static function getFormattedUbnOfBirthFromRecord($recordArray)
    {
        $value = MixBlupDataFileBase::getFormattedUbnOfBirthWithoutPadding($recordArray);
        return CsvWriterUtil::pad($value, MaxLength::UBN, true);
    }


    /**
     * Note! Gender/Type should already be filtered to only contain 'Ram' or 'Ewe'
     *
     * @param $data
     * @param string $key
     * @param bool $useColumnPadding
     * @return string
     */
    protected static function getFormattedGenderFromType($data, $key = JsonInputConstant::TYPE, $useColumnPadding = true)
    {
        $gender = MixBlupDataFileBase::translateGender($data[$key]);
        return CsvWriterUtil::pad($gender, MaxLength::VALID_GENDER, $useColumnPadding);
    }



    /**
     * @param array $recordArray
     * @return string
     */
    private static function getFormattedDateOfBirthFromRecord($recordArray)
    {
        return CsvWriterUtil::getFormattedValueFromArray($recordArray, MaxLength::DATE, JsonInputConstant::DATE_OF_BIRTH, true, MixBlupInstructionFileBase::PEDIGREE_FILE_DATE_OF_BIRTH_NULL_REPLACEMENT);
    }


    /**
     * @param array $recordArray
     * @return string
     */
    private static function getFormattedUlnFromRecord($recordArray)
    {
        return CsvWriterUtil::getFormattedValueFromArray($recordArray, MaxLength::ULN, JsonInputConstant::ULN, true);
    }

}