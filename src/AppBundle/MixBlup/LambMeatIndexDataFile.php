<?php


namespace AppBundle\MixBlup;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Constant\ReportLabel;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\MeasurementType;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\CsvWriterUtil;
use Doctrine\DBAL\Connection;

/**
 * Class LambMeatIndexDataFile
 * @package AppBundle\MixBlup
 */
class LambMeatIndexDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        // TODO: Implement generateDataFile() method.

        $baseValuesSearchArray = self::createBaseValuesSearchArray($conn);

        $animalId = 270893; //TODO remove PLACEHOLDER
        $baseRecordValues = $baseValuesSearchArray[$animalId];
        $recordBase = $baseRecordValues[ReportLabel::START];
        $recordEnding = $baseRecordValues[ReportLabel::END];

        return [];
    }


    /**
     * @inheritDoc
     */
    static function getSqlQueryRelatedAnimals()
    {
        $returnValuesString = 'a.id as '.JsonInputConstant::ANIMAL_ID.', a.'.JsonInputConstant::TYPE;
        return self::getSqlQueryForBaseValues($returnValuesString). ' GROUP BY a.id, a.type';
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function createBaseValuesSearchArray(Connection $conn)
    {
        $dynamicColumnWidths = self::dynamicColumnWidths($conn);

        $results = [];
        foreach ($conn->query(self::getSqlQueryForBaseValues())->fetchAll() as $data) {
            $parsedBreedCode = self::parseBreedCode($data);

            $recordBase =
                self::getFormattedUln($data).
                self::getFormattedUlnMother($data).
                self::getFormattedYearAndUbnOfBirth($data, $dynamicColumnWidths[JsonInputConstant::YEAR_AND_UBN_OF_BIRTH]).
                self::getFormattedGenderFromType($data).
                self::getFormattedLitterGroup($data).
                $parsedBreedCode.
                self::getFormattedHeterosis($data).
                self::getFormattedRecombination($data).
                self::getFormattedNLing($data).
                self::getFormattedSuckleCount($data);

            $recordEnding =
                self::getUbnOfBirthAsLastColumnValue($data);

            $results[$data[JsonInputConstant::ANIMAL_ID]] = [
                ReportLabel::START => $recordBase,
                ReportLabel::END => $recordEnding,
            ];
        }
        return $results;
    }


    /**
     * @param string $returnValuesString
     * @return string
     */
    private static function getSqlQueryForBaseValues($returnValuesString = null)
    {
        if($returnValuesString == null) {
            $returnValuesString =
                "a.id as ".JsonInputConstant::ANIMAL_ID.",
                 CONCAT(a.uln_country_code, a.uln_number) as ".JsonInputConstant::ULN.", a.".JsonInputConstant::TYPE.",
                 CONCAT(mom.uln_country_code, mom.uln_number) as ".JsonInputConstant::ULN_MOTHER.",
                 CONCAT(DATE_PART('year', a.date_of_birth),'_', a.ubn_of_birth) as ".JsonInputConstant::YEAR_AND_UBN_OF_BIRTH.",
                 CONCAT(mom.uln_country_code, mom.uln_number,'_', LPAD(CAST(l.litter_ordinal AS TEXT), 2, '0')) as ".JsonInputConstant::LITTER_GROUP.",
                 a.".JsonInputConstant::BREED_CODE.",
                 l.born_alive_count + l.stillborn_count as ".JsonInputConstant::N_LING.",
                 l.".JsonInputConstant::SUCKLE_COUNT.",
                 a.".JsonInputConstant::UBN_OF_BIRTH.",
                 a.".JsonInputConstant::HETEROSIS.",
                 a.".JsonInputConstant::RECOMBINATION.",
                 c.birth_weight, c.tail_length,
                 c.weight_at8weeks, c.age_weight_at8weeks,
                 c.weight_at20weeks, c.age_weight_at20weeks";
        }

        return "SELECT
                  ".$returnValuesString."
                FROM measurement m
                  INNER JOIN animal a ON a.id = x.animal_id
                  INNER JOIN animal mom ON mom.id = a.parent_mother_id
                  LEFT JOIN animal dad ON dad.id = a.parent_father_id
                  INNER JOIN litter l ON l.id = a.litter_id
                  LEFT JOIN animal_cache c ON c.animal_id = a.id
                WHERE 
                  ".self::getSqlBaseFilter();
    }


    /**
     * @return string
     */
    private static function getSqlBaseFilter()
    {
        return "m.is_active AND DATE_PART('year', NOW()) - DATE_PART('year', measurement_date) <= ".MixBlupSetting::MEASUREMENTS_FROM_LAST_AMOUNT_OF_YEARS."
                  AND a.gender <> '".GenderType::NEUTER."'
                  AND a.date_of_birth NOTNULL AND a.ubn_of_birth NOTNULL
                  AND m.measurement_date <= NOW()
                  AND (
                        m.type = '".MeasurementType::BODY_FAT."' OR
                        m.type = '".MeasurementType::MUSCLE_THICKNESS."' OR
                        m.type = '".MeasurementType::TAIL_LENGTH."' OR
                        m.type = '".MeasurementType::WEIGHT."'
                      )";
    }



}