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
        if ($breedType === BreedType::PURE_BRED) {
            return self::MAIN_SECTION;
        }

        if ($breedType === BreedType::SECONDARY_REGISTER || $breedType === BreedType::BLIND_FACTOR) {
            return self::COMPLEMENTARY_SECTION;
        }

        return $nullFiller;
    }
}