<?php

namespace AppBundle\Util;
use AppBundle\Entity\Animal;
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
    public static function translateBreedType($breedType, $isOnlyFirstLetterCapitilized = true)
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
        if($isOnlyFirstLetterCapitilized) {
            return ucfirst(strtolower($result));
        } else {
            return $result;
        }
    }

    /**
     * @param Animal $animal
     * @return string
     */
    public static function getGenderInDutch(Animal $animal)
    {
        /* variables translated to Dutch */
        $genderEnglish = $animal->getGender();
        if($genderEnglish == 'Ram' || $genderEnglish == GenderType::MALE || $genderEnglish == GenderType::M) {
            $genderDutch = 'Ram';
        } elseif ($genderEnglish == 'Ewe' || $genderEnglish == GenderType::FEMALE || $genderEnglish == GenderType::V) {
            $genderDutch = 'Ooi';
        } else {
            $genderDutch = 'Onbekend';
        }
        return $genderDutch;
    }

}