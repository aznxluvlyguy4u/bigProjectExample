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
    
}