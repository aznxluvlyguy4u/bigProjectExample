<?php


namespace AppBundle\Component\MixBlup;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Enumerator\ExteriorKind;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\TexelaarPedigreeRegisterAbbreviation;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\CsvWriterUtil;
use AppBundle\Util\Translation;
use AppBundle\Validation\ExteriorValidator;
use Doctrine\DBAL\Connection;

/**
 * Class ExteriorDataFile
 * @package AppBundle\MixBlup
 */
class ExteriorDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        $dynamicColumnWidths = self::dynamicColumnWidths($conn);

        $records = [];
        foreach (self::getDataBySql($conn) as $data)
        {
            /*
             * null check validation for these values is done in the sql query
             * uln, gender, litter_group, year_and_ubn_of_birth, breed_code, ubn_of_birth, kind
             */

            $parsedBreedCode = self::parseBreedCode($data);
            //Only allow valid breedCodes
            if(!$parsedBreedCode) { continue; }

            if($data[JsonInputConstant::HETEROSIS] == null
                || $data[JsonInputConstant::RECOMBINATION] == null) {
                //TODO
            }

            $formattedUln = MixBlupSetting::INCLUDE_ULNS ? self::getFormattedUln($data) : '';

            $record =
            $formattedUln.
            self::getFormattedAnimalId($data).
            self::getFormattedGenderFromType($data).
            self::getFormattedYearAndUbnOfBirth($data, $dynamicColumnWidths[JsonInputConstant::YEAR_AND_UBN_OF_BIRTH]).
            self::getFormattedLitterGroup($data).
            self::getFormattedNsfoInspectorCode($data).
            $parsedBreedCode.
            self::getFormattedHeterosis($data).
            self::getFormattedRecombination($data).
            self::formatMuscularityFemaleVG($data).
            self::formatMaleVGExteriorValues($data).
            self::formatDFExteriorValues($data).
            self::formatLinearExteriorValues().
            self::getUbnOfBirthAsLastColumnValue($data);

            $records[] = $record;
        }

        return $records;
    }


    /**
     * @param array $data
     * @return string
     */
    private static function formatMuscularityFemaleVG(array $data)
    {
        //null check validation for these values is done in the sql query
        $kind = $data[JsonInputConstant::KIND];
        $gender = self::getTranslatedGenderFromType($data);
        if($kind == ExteriorKind::VG_ && $gender == Translation::NL_EWE) {
            $muscularity = self::formatExteriorValue($data, JsonInputConstant::MUSCULARITY);
        } else {
            $muscularity = self::formattedNullExteriorValue();
        }
        return $muscularity;
    }


    /**
     * @param array $data
     * @return string
     */
    private static function formatMaleVGExteriorValues(array $data)
    {
        return self::formatExteriorValuesSet($data, [ExteriorKind::VG_], true);
    }


    /**
     * @param array $data
     * @return string
     */
    private static function formatDFExteriorValues(array $data)
    {
        return self::formatExteriorValuesSet($data, [ExteriorKind::DF_, ExteriorKind::DD_, ExteriorKind::HK_], false);
    }


    /**
     * @param array $data
     * @param array $allowedKinds
     * @param bool $onlyMale
     * @return string
     */
    private static function formatExteriorValuesSet(array $data, array $allowedKinds, $onlyMale = false)
    {
        //null check validation for these values is done in the sql query
        $kind = $data[JsonInputConstant::KIND];

        $isGenderAllowed = true;
        if($onlyMale) {
            $gender = self::getTranslatedGenderFromType($data);
            $isGenderAllowed = $gender == Translation::NL_RAM;
        }

        if(in_array($kind, $allowedKinds) && $isGenderAllowed) {
            $skull = self::formatExteriorValue($data, "skull");
            $progress = self::formatExteriorValue($data, "progress");
            $muscularity =self::formatExteriorValue($data, "muscularity");
            $proportion = self::formatExteriorValue($data, "proportion");
            $exteriorType = self::formatExteriorValue($data, "exterior_type");
            $legWork = self::formatExteriorValue($data, "leg_work");
            $fur = self::formatExteriorValue($data, "fur");
            $generalAppearance = self::formatExteriorValue($data, "general_appearance");
            $height = self::formatExteriorDecimalValue($data, "height");
            $breastDepth = self::formatExteriorDecimalValue($data, "breast_depth");
            $torsoLength = self::formatExteriorDecimalValue($data, "torso_length");
        } else {
            $skull = self::formattedNullExteriorValue();
            $progress = self::formattedNullExteriorValue();
            $muscularity = self::formattedNullExteriorValue();
            $proportion = self::formattedNullExteriorValue();
            $exteriorType = self::formattedNullExteriorValue();
            $legWork = self::formattedNullExteriorValue();
            $fur = self::formattedNullExteriorValue();
            $generalAppearance = self::formattedNullExteriorValue();
            $height = self::formattedNullExteriorDecimalValue();
            $breastDepth = self::formattedNullExteriorDecimalValue();
            $torsoLength = self::formattedNullExteriorDecimalValue();
        }

        return $skull.$progress.$muscularity.$proportion.$exteriorType.
        $legWork.$fur.$generalAppearance.$height.$breastDepth.$torsoLength;
    }


    /**
     * @return string
     */
    private static function formatLinearExteriorValues()
    {
        //At the moment all linearExteriorValues are null
        return
            self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::formattedNullExteriorValue()
            .self::getFormattedLinearNsfoInspectorCode([JsonInputConstant::LINEAR_INSPECTOR_CODE => null]);
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function getDataBySql(Connection $conn)
    {
        return $conn->query(self::getSqlQuery())->fetchAll();
    }


    /**
     * @param string $returnValuesString
     * @return string
     */
    private static function getSqlQuery($returnValuesString = null)
    {
        $minVal = ExteriorValidator::DEFAULT_MIN_EXTERIOR_VALUE;
        $maxVal = ExteriorValidator::DEFAULT_MAX_EXTERIOR_VALUE;

        if($returnValuesString == null) {
            $returnValuesString =
                " a.id as animal_id,
                  CONCAT(a.uln_country_code, a.uln_number) as uln,
                  a.type,
                  CONCAT(DATE_PART('year', a.date_of_birth),'_', a.ubn_of_birth) as year_and_ubn_of_birth,
                  CONCAT(mom.uln_country_code, mom.uln_number,'_', LPAD(CAST(l.litter_ordinal AS TEXT), 2, '0')) as litter_group,
                  a.breed_code,
                  i.inspector_code,
                  a.ubn_of_birth,
                  a.heterosis,
                  a.recombination,
                  skull, muscularity, proportion, progress, exterior_type, leg_work, fur,
                  general_appearance, height, breast_depth, torso_length, kind,
                  r.abbreviation as pedigree_register";
        }

        $sqlBase = "SELECT
                  ".$returnValuesString."
                FROM exterior x
                  INNER JOIN measurement m ON m.id = x.id
                  INNER JOIN animal a ON a.id = x.animal_id
                  INNER JOIN animal mom ON mom.id = a.parent_mother_id
                  INNER JOIN inspector i ON i.id = m.inspector_id
                  INNER JOIN litter l ON l.id = a.litter_id
                  LEFT JOIN pedigree_register r ON r.id = a.pedigree_register_id
                WHERE m.is_active AND DATE_PART('year', NOW()) - DATE_PART('year', measurement_date) <= 
                  ".MixBlupSetting::MEASUREMENTS_FROM_LAST_AMOUNT_OF_YEARS."
                  AND a.gender <> '".GenderType::NEUTER."'
                  AND a.date_of_birth NOTNULL AND a.ubn_of_birth NOTNULL AND a.uln_number NOTNULL
                  AND a.breed_code NOTNULL AND m.measurement_date NOTNULL
                  --AND a.heterosis NOTNULL AND a.recombination NOTNULL --CHECK IF NULLCHECK NECESSARY OR NOT
                  AND m.measurement_date <= NOW()
                  AND i.inspector_code NOTNULL
                  AND NOT(
                          (skull < ".$minVal." OR skull > ".$maxVal.") AND
                          (progress < ".$minVal." OR progress > ".$maxVal.") AND
                          (muscularity < ".$minVal." OR muscularity > ".$maxVal.") AND
                          (proportion < ".$minVal." OR proportion > ".$maxVal.") AND
                          (exterior_type < ".$minVal." OR exterior_type > ".$maxVal.") AND
                          (leg_work < ".$minVal." OR leg_work > ".$maxVal.") AND
                          (fur < ".$minVal." OR fur > ".$maxVal.") AND
                          (general_appearance < ".$minVal." OR general_appearance > ".$maxVal.") AND
                          (height = 0 OR height > ".$maxVal.") AND
                          (torso_length = 0 OR torso_length > ".$maxVal.") AND
                          (breast_depth = 0 OR breast_depth > ".$maxVal.")
                          )
                  AND (
                        -- 1 VG FEMALE
                        (
                          a.gender = '".GenderType::FEMALE."' AND x.kind = '".ExteriorKind::VG_."'
                          AND ".$minVal." <= x.muscularity OR x.muscularity <= ".$maxVal."
                        )
                        OR
                        -- 2 VG MALE
                        (
                          a.gender = '".GenderType::MALE."' AND x.kind = '".ExteriorKind::VG_."'
                        )
                        OR
                        -- 3 DF/DD/HK
                        (
                          x.kind = '".ExteriorKind::DD_."' OR x.kind = '".ExteriorKind::DF_."' OR x.kind = '".ExteriorKind::HK_."'
                        )
                      )".self::getErrorLogAnimalPedigreeFilter('a.id');
        
        $sql = $sqlBase."
            ".self::generateNonTexelaarFilter().
            "
            UNION
            ".$sqlBase."
            ".self::generateTexelaarFilter();

        return $sql;
    }


    /**
     * @return string
     */
    public static function getSqlQueryRelatedAnimals()
    {
        $returnValuesString = 'a.id as '.JsonInputConstant::ANIMAL_ID.', a.'.JsonInputConstant::TYPE;
        return self::getSqlQuery($returnValuesString). ' GROUP BY a.id, a.type';
    }


    /**
     * @param $array
     * @param $key
     * @param int $maxLength
     * @return string
     */
    private static function formatExteriorValue($array, $key, $maxLength = MaxLength::EXTERIOR_VALUE)
    {
        $filledExteriorValue = Utils::fillZero($array[$key], ExteriorInstructionFiles::MISSING_REPLACEMENT);
        return CsvWriterUtil::pad($filledExteriorValue, $maxLength);
    }


    /**
     * @param int $maxLength
     * @return string
     */
    private static function formattedNullExteriorValue($maxLength = MaxLength::EXTERIOR_VALUE)
    {
        return CsvWriterUtil::pad(ExteriorInstructionFiles::MISSING_REPLACEMENT, $maxLength);
    }


    /**
     * @param $array
     * @param $key
     * @return string
     */
    private static function formatExteriorDecimalValue($array, $key)
    {
        return self::formatExteriorValue($array, $key, MaxLength::EXTERIOR_DECIMAL_VALUE);
    }


    /**
     * @return string
     */
    private static function formattedNullExteriorDecimalValue()
    {
        return self::formattedNullExteriorValue(MaxLength::EXTERIOR_DECIMAL_VALUE);
    }


    /**
     * @return string
     */
    private static function generateNonTexelaarFilter()
    {
        $filterString = 'AND ((';
        $prefix = '';
        foreach (TexelaarPedigreeRegisterAbbreviation::getAll() as $abbreviation) {
            $filterString = $filterString.$prefix."r.abbreviation <> '".$abbreviation."'";
            $prefix = ' AND ';
        }
        $filterString = $filterString.") OR r.abbreviation ISNULL)";

        return $filterString;
    }


    /**
     * For 'Texelaars' and 'Blauwe Texelaars' the exterior measurements MUST be from NSFO inspectors.
     * Measurements from other non-NSFO inspectors for those pedigrees are not consistent enough in the NSFO context.
     * For other pedigrees the inspector type is not relevant in the NSFO context.
     *
     * @return string
     */
    private static function generateTexelaarFilter()
    {
        $filterString = 'AND (';
        $prefix = '';
        foreach (TexelaarPedigreeRegisterAbbreviation::getAll() as $abbreviation) {
            $filterString = $filterString.$prefix."r.abbreviation = '".$abbreviation."'";
            $prefix = ' OR ';
        }
        $filterString = $filterString.") AND m.inspector_id NOTNULL";
        //TODO include a way to filter for only NSFO inspectors

        return $filterString;
    }
}