<?php


namespace AppBundle\Util;


use AppBundle\Enumerator\BreedType;

class SectionUtil
{
    const MAIN_SECTION = 'Hoofdsectie';
    const COMPLEMENTARY_SECTION = 'Aanvullende sectie';

    /**
     * @param string $breedType
     * @param string $nullFiller
     * @return string
     */
    public static function getSectionType($breedType, $nullFiller = '')
    {
        if (
            $breedType === BreedType::PURE_BRED     // Volbloed
         || $breedType === BreedType::REGISTER      // Register
         || $breedType === BreedType::BLIND_FACTOR  // Blindfactor
        ) {
            return self::MAIN_SECTION;
        }

        if (
            $breedType === BreedType::SECONDARY_REGISTER // Hulpboek
         || $breedType === BreedType::UNDETERMINED       // Onbepaald
        ) {
            return self::COMPLEMENTARY_SECTION;
        }

        return $nullFiller;
    }
}