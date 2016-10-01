<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Enumerator\BreedCodeType;
use Doctrine\Common\Persistence\ObjectManager;

class BreedValueUtil
{
    const DEFAULT_AGE_NULL_FILLER = '-';
    const DEFAULT_GROWTH_NULL_FILLER = '-';
    const DEFAULT_WEIGHT_NULL_FILLER = '-';
    const MOTHER = 'mother';
    const FATHER = 'father';
    const HETEROSIS = 'heterosis';
    const RECOMBINATION = 'recombination';
    const EIGHT_PART_DENOMINATOR = 64;
    const HUNDRED_PART_DENOMINATOR = 10000;
    const IS_IGNORE_INCOMPLETE_CODES = true;
    const DEFAULT_DECIMAL_SYMBOL = '.';
    

    /**
     * @param float $weightOnThatMoment
     * @param int $ageInDays
     * @param int|string $ageNullFiller
     * @param int|string $growthNullFiller
     * @param int|string $weightNullFiller
     * @return float
     */
    public static function getGrowthValue($weightOnThatMoment, $ageInDays,
                                          $ageNullFiller = self::DEFAULT_AGE_NULL_FILLER,
                                          $growthNullFiller = self::DEFAULT_GROWTH_NULL_FILLER,
                                          $weightNullFiller = self::DEFAULT_WEIGHT_NULL_FILLER,
                                          $decimalSymbol = self::DEFAULT_DECIMAL_SYMBOL)
    {
        if($weightOnThatMoment == null || $weightOnThatMoment == 0 || $weightOnThatMoment == $weightNullFiller
            || $ageInDays == null || $ageInDays == 0 || $ageInDays == $ageNullFiller) {
            return $growthNullFiller;
        } else {
            return number_format($weightOnThatMoment / $ageInDays, 5, $decimalSymbol, '');
        }
    }


    /**
     * If the breedCodes are by parts of 8, the denominator should be 64.
     * If the breedCodes are by parts of 100, the denominator should be 10.000.
     *
     * @param ObjectManager $em
     * @param int $animalId
     * @param int $roundingAccuracy
     * @return float
     */
    public static function getHeterosisAndRecombinationBy8Parts(ObjectManager $em, $animalId, $roundingAccuracy = null)
    {
        $parentBreedCodes = self::getBreedCodesValuesOfParents($em, $animalId);

        //Null checks, null checks everywhere
        if($parentBreedCodes == null) { return null; }

        $valuesFather = Utils::getNullCheckedArrayValue(self::FATHER, $parentBreedCodes);
        $valuesMother = Utils::getNullCheckedArrayValue(self::MOTHER, $parentBreedCodes);

        if($valuesFather == null || $valuesMother == null) { return null; }
        if(count($valuesFather) == 0 || count($valuesMother) == 0 ) { return null; }

        $codesFather = array_keys($valuesFather);
        $codesMother = array_keys($valuesMother);


        if(self::IS_IGNORE_INCOMPLETE_CODES) {
            $hasIncompleteCodes = self::hasUnknownBreedCodes($valuesMother)
                               || self::hasUnknownBreedCodes($valuesFather);
            if($hasIncompleteCodes) {
                return null;
            }
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
            $heterosisValue = round($heterosisValue, $roundingAccuracy);
            $recombinationValue = round($recombinationValue, $roundingAccuracy);
        }

        return [
            self::HETEROSIS => $heterosisValue,
            self::RECOMBINATION => $recombinationValue
        ];
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @return array|null
     */
    private static function getBreedCodesValuesOfParents(ObjectManager $em, $animalId)
    {
        $sql = "SELECT parent_father_id as father, parent_mother_id as mother FROM animal WHERE id = '".$animalId."'";
        $result = $em->getConnection()->query($sql)->fetch();
        $motherId = $result[self::MOTHER];
        $fatherId = $result[self::FATHER];

        //Null check parents
        if($motherId == null || $fatherId == null) {
            return null;
        }

        $valuesMother = self::getBreedCodes($em, $motherId);
        $valuesFather = self::getBreedCodes($em, $fatherId);

        //Null check breedCodes
        if(count($valuesMother) == null || count($valuesFather) == null) {
            return null;
        }

        return [self::MOTHER => $valuesMother, self::FATHER => $valuesFather];
    }


    /**
     * @param ObjectManager $em
     * @param $animalId
     * @return array
     */
    public static function getBreedCodes(ObjectManager $em, $animalId)
    {
        $codes = array();

        $sql = "SELECT breed_code.name as name, breed_code.value as value FROM breed_code INNER JOIN breed_codes ON breed_code.breed_codes_id = breed_codes.id WHERE breed_codes.animal_id = '".$animalId."'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        foreach ($results as $result) {
            $codes[$result['name']] = $result['value'];
        }

        return $codes;
    }


    /**
     * @param array $breedCodeArray
     * @return bool
     */
    public static function hasUnknownBreedCodes($breedCodeArray)
    {
        $unknownCodeExists = array_key_exists(BreedCodeType::NN, $breedCodeArray) || array_key_exists(BreedCodeType::OV, $breedCodeArray);
        if($unknownCodeExists) {
            return true;
        } else {
            $sum = 0;
            foreach ($breedCodeArray as $item) {
                $sum += $item;
            }
            $isValuesIncomplete = $sum < 8;
            if($isValuesIncomplete) {
                return true;
            }
        }

        return false;
    }


}