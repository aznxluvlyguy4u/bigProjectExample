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
        if (in_array($breedType, self::mainSectionBreedTypes())) {
            return self::MAIN_SECTION;
        }

        if (in_array($breedType, self::secondarySectionBreedTypes())) {
            return self::COMPLEMENTARY_SECTION;
        }

        return $nullFiller;
    }

    public static function mainSectionBreedTypes(): array {
        return [
            BreedType::PURE_BRED,       // Volbloed
            BreedType::REGISTER,        // Register
            BreedType::BLIND_FACTOR,    // Blindfactor
        ];
    }

    public static function secondarySectionBreedTypes(): array {
        return [
            BreedType::SECONDARY_REGISTER,  // Hulpboek
            BreedType::UNDETERMINED,        // Onbepaald
        ];
    }
}