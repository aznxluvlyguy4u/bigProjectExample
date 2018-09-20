<?php


namespace AppBundle\Util;


use AppBundle\Enumerator\BreedType;

class SectionUtil
{
    const MAIN_SECTION = 'Hoofdsectie';
    const COMPLEMENTARY_SECTION = 'Aanvullende sectie';

    /**
     * @param string $breedType
     * @return string
     */
    public static function getSectionType($breedType)
    {
        if ($breedType === BreedType::SECONDARY_REGISTER || $breedType === BreedType::UNDETERMINED) {
            return self::COMPLEMENTARY_SECTION;
        }
        return self::MAIN_SECTION;
    }
}