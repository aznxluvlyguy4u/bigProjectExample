<?php

namespace AppBundle\MixBlup;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvWriterUtil;
use AppBundle\Util\MixBlupPedigreeUtil;
use Doctrine\DBAL\Connection;

/**
 * Class MixblupPedigreeFileGenerator
 * @package AppBundle\MixBlup
 */
class MixblupPedigreeFileGenerator
{
    
    
    /**
     * @param Connection $conn
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    public static function generateExteriorOptimizedSet(Connection $conn, $cmdUtil = null)
    {
        return self::generateSet($conn, $cmdUtil, MixBlupType::EXTERIOR);
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    public static function generateFertilityOptimizedSet(Connection $conn, $cmdUtil = null)
    {
        return self::generateSet($conn, $cmdUtil, MixBlupType::FERTILITY);
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    public static function generateLambMeatIndexOptimizedSet(Connection $conn, $cmdUtil = null)
    {
        return self::generateSet($conn, $cmdUtil, MixBlupType::LAMB_MEAT_INDEX);
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    public static function generateFullSet(Connection $conn, $cmdUtil = null)
    {
        return self::generateSet($conn, $cmdUtil);
    }
    

    /**
     * @param Connection $conn
     * @param string $getOptimizedSet
     * @param CommandUtil|null $cmdUtil
     * @return array
     */
    private static function generateSet(Connection $conn, $cmdUtil = null, $getOptimizedSet = null)
    {
        $mixBlupPedigreeUtil = new MixBlupPedigreeUtil($conn, $cmdUtil);

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
            self::getFormattedUlnFromRecord(JsonInputConstant::ULN, $recordArray).
            self::getFormattedUlnFromRecord(JsonInputConstant::ULN_FATHER, $recordArray).
            self::getFormattedUlnFromRecord(JsonInputConstant::ULN_MOTHER, $recordArray).
            MixBlupDataFileBase::getUbnOfBirthAsLastColumnValue($recordArray);
    }


    /**
     * @param string $key
     * @param array $recordArray
     * @return string
     */
    private static function getFormattedUlnFromRecord($key, $recordArray)
    {
        return CsvWriterUtil::getFormattedValueFromArray($recordArray, MaxLength::ULN, $key, true, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
    }

}