<?php

namespace AppBundle\Util;
use AppBundle\Constant\UnicodeSymbol;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\BirthType;
use AppBundle\Enumerator\BirthTypeDutch;
use AppBundle\Enumerator\BreedTrait;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\BreedTypeDutch;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\PredicateType;
use AppBundle\Enumerator\PredicateTypeDutch;

/**
 * This class translates the English used in the API to Dutch values displayed in the output.
 *
 * When more translations are needed and there is time, implement a proper translation table.
 * http://symfony.com/doc/current/components/translation/index.html
 *
 * Class Translation
 * @package AppBundle\Util
 */
class Translation
{

    /**
     * @param string $breedType
     * @return string
     */
    public static function getFirstLetterTranslatedBreedType($breedType)
    {
        return strtoupper(substr(self::translateBreedType($breedType), 0, 1));
    }

    /**
     * @param string $breedType
     * @param bool $isOnlyFirstLetterCapitalized
     * @return string
     */
    public static function translateBreedType($breedType, $isOnlyFirstLetterCapitalized = true)
    {
        switch ($breedType) {
            case BreedType::BLIND_FACTOR:       $result = BreedTypeDutch::BLIND_FACTOR; break;
            case BreedType::MEAT_LAMB_FATHER :  $result = BreedTypeDutch::MEAT_LAMB_FATHER; break;
            case BreedType::PARENT_ANIMAL:      $result = BreedTypeDutch::PARENT_ANIMAL; break;
            case BreedType::PURE_BRED:          $result = BreedTypeDutch::PURE_BRED; break;
            case BreedType::REGISTER:           $result = BreedTypeDutch::REGISTER; break;
            case BreedType::SECONDARY_REGISTER: $result = BreedTypeDutch::SECONDARY_REGISTER; break;
            case BreedType::UNDETERMINED:       $result = BreedTypeDutch::UNDETERMINED; break;
            default: $result = $breedType; break; //no translation
        }
        if($isOnlyFirstLetterCapitalized) {
            return ucfirst(strtolower($result));
        } else {
            return $result;
        }
    }

    /**
     * @param Animal $animal
     * @return string
     */
    public static function getGenderInDutchByAnimal(Animal $animal)
    {
        return self::getGenderInDutch($animal->getGender());
    }


    /**
     * @param string $genderEnglish
     * @param string $neuterString
     * @return string
     */
    public static function getGenderInDutch($genderEnglish, $neuterString = 'Onbekend')
    {
        /* variables translated to Dutch */
        if($genderEnglish == 'Ram' || $genderEnglish == GenderType::MALE || $genderEnglish == GenderType::M) {
            $genderDutch = 'Ram';
        } elseif ($genderEnglish == 'Ewe' || $genderEnglish == GenderType::FEMALE || $genderEnglish == GenderType::V) {
            $genderDutch = 'Ooi';
        } else {
            $genderDutch = $neuterString;
        }
        return $genderDutch;
    }


    /**
     * @param string $genderEnglish
     * @param string $neuterString
     * @return string
     */
    public static function getGenderAsUnicodeSymbol($genderEnglish, $neuterString = '-')
    {
        /* variables translated to Dutch */
        if($genderEnglish == 'Ram' || $genderEnglish == GenderType::MALE || $genderEnglish == GenderType::M) {
            $genderUnicodeSymbol = UnicodeSymbol::MALE();
        } elseif ($genderEnglish == 'Ewe' || $genderEnglish == GenderType::FEMALE || $genderEnglish == GenderType::V) {
            $genderUnicodeSymbol = UnicodeSymbol::FEMALE();
        } else {
            $genderUnicodeSymbol = $neuterString;
        }
        return $genderUnicodeSymbol;
    }


    /**
     * @param string $birthTypeDutch
     * @param bool $isOnlyFirstLetterCapitalized
     * @return string
     */
    public static function getBirthTypeInEnglish($birthTypeDutch, $isOnlyFirstLetterCapitalized = true)
    {
        switch ($birthTypeDutch) {
            case BirthTypeDutch::NO_HELP:                       $result = BirthType::NO_HELP; break;
            case BirthTypeDutch::LIGHT_WITH_HELP:               $result = BirthType::LIGHT_WITH_HELP; break;
            case BirthTypeDutch::NORMAL_WITH_HELP:              $result = BirthType::NORMAL_WITH_HELP; break;
            case BirthTypeDutch::HEAVY_WITH_HELP:               $result = BirthType::HEAVY_WITH_HELP; break;
            case BirthTypeDutch::CAESARIAN_LAMB_TOO_BIG:        $result = BirthType::CAESARIAN_LAMB_TOO_BIG; break;
            case BirthTypeDutch::CAESARIAN_INSUFFICIENT_ACCESS: $result = BirthType::CAESARIAN_INSUFFICIENT_ACCESS; break;
            default: $result = $birthTypeDutch; break; //no translation
        }
        if($isOnlyFirstLetterCapitalized) {
            return ucfirst(strtolower($result));
        } else {
            return $result;
        }
    }


    /**
     * @param string $predicateTypeDutch
     * @param bool $isOnlyFirstLetterCapitalized
     * @return string
     */
    public static function getPredicateTypeInEnglish($predicateTypeDutch, $isOnlyFirstLetterCapitalized = true)
    {
        switch ($predicateTypeDutch) {
            case PredicateTypeDutch::DEFINITIVE_PREMIUM_RAM:     $result = PredicateType::DEFINITIVE_PREMIUM_RAM; break;
            case PredicateTypeDutch::GRADE_RAM:                  $result = PredicateType::GRADE_RAM; break;
            case PredicateTypeDutch::PREFERENT:                  $result = PredicateType::PREFERENT; break;
            case PredicateTypeDutch::PREFERENT_1:                $result = PredicateType::PREFERENT_1; break;
            case PredicateTypeDutch::PREFERENT_2:                $result = PredicateType::PREFERENT_2; break;
            case PredicateTypeDutch::PREFERENT_A:                $result = PredicateType::PREFERENT_A; break;
            case PredicateTypeDutch::PRIME_RAM:                  $result = PredicateType::PRIME_RAM; break;
            case PredicateTypeDutch::MOTHER_OF_RAMS:             $result = PredicateType::MOTHER_OF_RAMS; break;
            case PredicateTypeDutch::STAR_EWE:                   $result = PredicateType::STAR_EWE; break;
            case PredicateTypeDutch::STAR_EWE_1:                 $result = PredicateType::STAR_EWE_1; break;
            case PredicateTypeDutch::STAR_EWE_2:                 $result = PredicateType::STAR_EWE_2; break;
            case PredicateTypeDutch::STAR_EWE_3:                 $result = PredicateType::STAR_EWE_3; break;
            case PredicateTypeDutch::PROVISIONAL_MOTHER_OF_RAMS: $result = PredicateType::PROVISIONAL_MOTHER_OF_RAMS; break;
            case PredicateTypeDutch::PROVISIONAL_PRIME_RAM:      $result = PredicateType::PROVISIONAL_PRIME_RAM; break;
            default: $result = $predicateTypeDutch; break; //no translation
        }
        if($isOnlyFirstLetterCapitalized) {
            return ucfirst(strtolower($result));
        } else {
            return $result;
        }
    }


    /**
     * @param string $predicateTypeEnglish
     * @param bool $isOnlyFirstLetterCapitalized
     * @return string
     */
    public static function getPredicateTypeInDutch($predicateTypeEnglish, $isOnlyFirstLetterCapitalized = true)
    {
        switch ($predicateTypeEnglish) {
            case PredicateType::DEFINITIVE_PREMIUM_RAM:     $result = PredicateTypeDutch::DEFINITIVE_PREMIUM_RAM; break;
            case PredicateType::GRADE_RAM:                  $result = PredicateTypeDutch::GRADE_RAM; break;
            case PredicateType::PREFERENT:                  $result = PredicateTypeDutch::PREFERENT; break;
            case PredicateType::PREFERENT_1:                $result = PredicateTypeDutch::PREFERENT_1; break;
            case PredicateType::PREFERENT_2:                $result = PredicateTypeDutch::PREFERENT_2; break;
            case PredicateType::PREFERENT_A:                $result = PredicateTypeDutch::PREFERENT_A; break;
            case PredicateType::PRIME_RAM:                  $result = PredicateTypeDutch::PRIME_RAM; break;
            case PredicateType::MOTHER_OF_RAMS:             $result = PredicateTypeDutch::MOTHER_OF_RAMS; break;
            case PredicateType::STAR_EWE:                   $result = PredicateTypeDutch::STAR_EWE; break;
            case PredicateType::STAR_EWE_1:                 $result = PredicateTypeDutch::STAR_EWE_1; break;
            case PredicateType::STAR_EWE_2:                 $result = PredicateTypeDutch::STAR_EWE_2; break;
            case PredicateType::STAR_EWE_3:                 $result = PredicateTypeDutch::STAR_EWE_3; break;
            case PredicateType::PROVISIONAL_MOTHER_OF_RAMS: $result = PredicateTypeDutch::PROVISIONAL_MOTHER_OF_RAMS; break;
            case PredicateType::PROVISIONAL_PRIME_RAM:      $result = PredicateTypeDutch::PROVISIONAL_PRIME_RAM; break;
            default: $result = $predicateTypeEnglish; break; //no translation
        }
        if($isOnlyFirstLetterCapitalized) {
            return ucfirst(strtolower($result));
        } else {
            return $result;
        }
    }
    
    
}