<?php


namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\ReportLabel;
use AppBundle\Enumerator\BreedCodeType;
use Doctrine\DBAL\Connection;

/**
 * Class HeterosisAndRecombinationUtil
 * @package AppBundle\Util
 */
class HeterosisAndRecombinationUtil
{
    const EIGHT_PART_DENOMINATOR = 64;
    const HUNDRED_PART_DENOMINATOR = 10000;
    const IS_IGNORE_INCOMPLETE_CODES = true;


    /**
     * If the breedCodes are by parts of 8, the denominator should be 64.
     * If the breedCodes are by parts of 100, the denominator should be 10.000.
     *
     * @param Connection $conn
     * @param int $animalId
     * @param int $roundingAccuracy
     * @return array|null
     */
    public static function getHeterosisAndRecombinationByAnimalId(Connection $conn, $animalId, $roundingAccuracy = null)
    {
        $sql = "SELECT f.breed_code as father_breed_code, m.breed_code as mother_breed_code
                FROM animal c
                  LEFT JOIN animal f ON f.id = c.parent_father_id
                  LEFT JOIN animal m ON m.id = c.parent_mother_id 
                  WHERE c.id = ".$animalId;
        $result = $conn->query($sql)->fetch();

        return self::getHeterosisAndRecombinationBy8Parts($result['father_breed_code'], $result['mother_breed_code'], $roundingAccuracy);
    }


    /**
     * @param $breedCodeStringFather
     * @param $breedCodeStringMother
     * @param int|null $roundingAccuracy
     * @param boolean $returnWithTrailingZeroes
     * @return array|null
     */
    public static function getHeterosisAndRecombinationBy8Parts($breedCodeStringFather, $breedCodeStringMother,
                                                                $roundingAccuracy = null, $returnWithTrailingZeroes = false)
    {
        $valuesFather = BreedCodeUtil::getBreedCodeAs8PartsFromBreedCodeString($breedCodeStringFather);
        $valuesMother = BreedCodeUtil::getBreedCodeAs8PartsFromBreedCodeString($breedCodeStringMother);

        if($valuesFather == null || $valuesMother == null) { return null; }
        if(count($valuesFather) == 0 || count($valuesMother) == 0 ) { return null; }

        $codesFather = array_keys($valuesFather);
        $codesMother = array_keys($valuesMother);

        if(self::IS_IGNORE_INCOMPLETE_CODES && (self::hasUnknownBreedCodes($valuesMother) || self::hasUnknownBreedCodes($valuesFather))) {
            return null;
        }

        /* Calculate values */
        $heterosisSum = 0.0;
        $recombinationSum = 0.0;

        //Calculate heterosis
        foreach ($codesMother as $codeMother) {
            foreach ($codesFather as $codeFather) {
                if($codeFather != $codeMother) {
                    $heterosisSum += $valuesMother[$codeMother] * $valuesFather[$codeFather];
                }
            }
        }

        //Calculate recombination
        $recombinationSum += Utils::getPermutationProduct($valuesFather);
        $recombinationSum += Utils::getPermutationProduct($valuesMother);

        $heterosisValue = (float)$heterosisSum/self::EIGHT_PART_DENOMINATOR;
        $recombinationValue = (float)$recombinationSum/self::EIGHT_PART_DENOMINATOR;

        if($roundingAccuracy != null) {
            if($returnWithTrailingZeroes) {
                $heterosisValue = round($heterosisValue, $roundingAccuracy);
                $recombinationValue = round($recombinationValue, $roundingAccuracy);
            } else {
                $heterosisValue = round($heterosisValue, $roundingAccuracy);
                $recombinationValue = round($recombinationValue, $roundingAccuracy);   
            }
        }

        return [
            ReportLabel::HETEROSIS => $heterosisValue,
            ReportLabel::RECOMBINATION => $recombinationValue
        ];
    }


    /**
     * @param array $breedCodeArray
     * @return bool
     */
    public static function hasUnknownBreedCodes($breedCodeArray)
    {
        $unknownCodeExists = key_exists(BreedCodeType::NN, $breedCodeArray) || key_exists(BreedCodeType::OV, $breedCodeArray);
        if($unknownCodeExists) {
            return true;
        }
        $areValuesIncomplete = array_sum($breedCodeArray) < 8;
        return $areValuesIncomplete;
    }

}