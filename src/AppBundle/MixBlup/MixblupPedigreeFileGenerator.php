<?php

namespace AppBundle\MixBlup;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Util\CsvWriterUtil;
use Doctrine\DBAL\Connection;

/**
 * Class MixblupPedigreeFileGenerator
 * @package AppBundle\MixBlup
 */
class MixblupPedigreeFileGenerator
{

    /**
     * @param Connection $conn
     * @return array
     */
    public static function generateFullSet(Connection $conn)
    {
        $records = [];
        foreach (self::retrieveDataFullSet($conn) as $recordArray)
        {
            $records[] = self::getFormattedPedigreeRecord($recordArray);
        }
        return $records;
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function retrieveDataFullSet(Connection $conn)
    {
        $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT;

        $sql = "SELECT
                  a.id as ".JsonInputConstant::ANIMAL_ID.",
                  CONCAT(a.uln_country_code, a.uln_number) AS ".JsonInputConstant::ULN.",
                  COALESCE(NULLIF(CONCAT(f.uln_country_code, f.uln_number),''), '".$nullReplacement."') AS ".JsonInputConstant::ULN_FATHER.",
                  COALESCE(NULLIF(CONCAT(m.uln_country_code, m.uln_number),''), '".$nullReplacement."') AS ".JsonInputConstant::ULN_MOTHER.",
                  a.ubn_of_birth AS ".JsonInputConstant::UBN_OF_BIRTH."
                FROM animal a
                  LEFT JOIN animal f ON f.id = a.parent_father_id
                  LEFT JOIN animal m ON m.id = a.parent_mother_id
                WHERE a.type <> 'Neuter'";
        return $conn->query($sql)->fetchAll();
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