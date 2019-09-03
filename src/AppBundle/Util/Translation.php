<?php

namespace AppBundle\Util;
use AppBundle\Constant\UnicodeSymbol;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\BirthType;
use AppBundle\Enumerator\BirthTypeDutch;
use AppBundle\Enumerator\BlindnessFactorType;
use AppBundle\Enumerator\BlindnessFactorTypeDutch;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\BreedTypeDutch;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\PredicateType;
use AppBundle\Enumerator\PredicateTypeDutch;
use AppBundle\Enumerator\ReasonOfDepartType;
use AppBundle\Enumerator\ReasonOfLossType;
use AppBundle\Enumerator\TreatmentTypeOption;
use Symfony\Component\Translation\TranslatorInterface;

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
    const NL_RAM = 'Ram';
    const NL_EWE = 'Ooi';
    const NL_NEUTER = 'Onbekend';

    /**
     * @param string $breedType
     * @return string
     */
    public static function getFirstLetterTranslatedBreedType($breedType)
    {
        return StringUtil::getCapitalizedFirstLetter(self::getDutch($breedType));
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
    public static function getGenderInDutch($genderEnglish, $neuterString = null)
    {
        $neuterString = $neuterString == null ? self::NL_NEUTER : $neuterString;

        /* variables translated to Dutch */
        if($genderEnglish == 'Ram' || $genderEnglish == GenderType::MALE || $genderEnglish == GenderType::M) {
            $genderDutch = self::NL_RAM;
        } elseif ($genderEnglish == 'Ewe' || $genderEnglish == GenderType::FEMALE || $genderEnglish == GenderType::V) {
            $genderDutch = self::NL_EWE;
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
     * @param string $dutchString
     * @return string
     */
    public static function getEnglishUcFirst($dutchString) { return self::getEnglish($dutchString, true); }


    /**
     * @param string $dutchString
     * @param boolean $isOnlyFirstLetterCapitalized
     * @return string
     */
    public static function getEnglish($dutchString, $isOnlyFirstLetterCapitalized = false)
    {
        $englishString = strtr($dutchString, array_flip(self::getEnglishToDutchTranslationArray()));

        if($isOnlyFirstLetterCapitalized) {
            return ucfirst(strtolower($englishString));
        } else {
            return $englishString;
        }
    }


    /**
     * @param string $englishString
     * @return string
     */
    public static function getDutchUcFirst($englishString) { return self::getDutch($englishString, true); }

    /**
     * @param $englishString
     * @param boolean $isOnlyFirstLetterCapitalized
     * @return string
     */
    public static function getDutch($englishString, $isOnlyFirstLetterCapitalized = false)
    {
        $dutchString = strtr($englishString, self::getEnglishToDutchTranslationArray());

        if($isOnlyFirstLetterCapitalized) {
            return ucfirst(strtolower($dutchString));
        } else {
            return $dutchString;
        }
    }

    /**
     * @return array
     */
    private static function getEnglishToDutchTranslationArray()
    {
        return array(
            //BirthType
            BirthType::NO_HELP                        => BirthTypeDutch::NO_HELP,
            BirthType::LIGHT_WITH_HELP                => BirthTypeDutch::LIGHT_WITH_HELP,
            BirthType::NORMAL_WITH_HELP               => BirthTypeDutch::NORMAL_WITH_HELP,
            BirthType::HEAVY_WITH_HELP                => BirthTypeDutch::HEAVY_WITH_HELP,
            BirthType::CAESARIAN_LAMB_TOO_BIG         => BirthTypeDutch::CAESARIAN_LAMB_TOO_BIG,
            BirthType::CAESARIAN_INSUFFICIENT_ACCESS  => BirthTypeDutch::CAESARIAN_INSUFFICIENT_ACCESS,
            //BlindnessFactor
            BlindnessFactorType::BLINDNESS_FACTOR_CARRIER => BlindnessFactorTypeDutch::BLINDNESS_FACTOR_CARRIER,
            BlindnessFactorType::BLINDNESS_FACTOR_FREE    => BlindnessFactorTypeDutch::BLINDNESS_FACTOR_FREE,
            //BreedType
            BreedType::BLIND_FACTOR        => BreedTypeDutch::BLIND_FACTOR,
            BreedType::MEAT_LAMB_FATHER    => BreedTypeDutch::MEAT_LAMB_FATHER,
            BreedType::MEAT_LAMB_MOTHER    => BreedTypeDutch::MEAT_LAMB_MOTHER,
            BreedType::PARENT_ANIMAL       => BreedTypeDutch::PARENT_ANIMAL,
            BreedType::PURE_BRED           => BreedTypeDutch::PURE_BRED,
            BreedType::REGISTER            => BreedTypeDutch::REGISTER,
            BreedType::SECONDARY_REGISTER  => BreedTypeDutch::SECONDARY_REGISTER,
            BreedType::UNDETERMINED        => BreedTypeDutch::UNDETERMINED,
            BreedType::EN_MANAGEMENT       => BreedTypeDutch::EN_MANAGEMENT,
            BreedType::EN_BASIS            => BreedTypeDutch::EN_BASIS,
            //PredicateType
            PredicateType::DEFINITIVE_PREMIUM_RAM     =>  PredicateTypeDutch::DEFINITIVE_PREMIUM_RAM,
            PredicateType::GRADE_RAM                  =>  PredicateTypeDutch::GRADE_RAM,
            PredicateType::PREFERENT                  =>  PredicateTypeDutch::PREFERENT,
            PredicateType::PREFERENT_1                =>  PredicateTypeDutch::PREFERENT_1,
            PredicateType::PREFERENT_2                =>  PredicateTypeDutch::PREFERENT_2,
            PredicateType::PREFERENT_A                =>  PredicateTypeDutch::PREFERENT_A,
            PredicateType::PRIME_RAM                  =>  PredicateTypeDutch::PRIME_RAM,
            PredicateType::MOTHER_OF_RAMS             =>  PredicateTypeDutch::MOTHER_OF_RAMS,
            PredicateType::STAR_EWE                   =>  PredicateTypeDutch::STAR_EWE,
            PredicateType::STAR_EWE_1                 =>  PredicateTypeDutch::STAR_EWE_1,
            PredicateType::STAR_EWE_2                 =>  PredicateTypeDutch::STAR_EWE_2,
            PredicateType::STAR_EWE_3                 =>  PredicateTypeDutch::STAR_EWE_3,
            PredicateType::PROVISIONAL_MOTHER_OF_RAMS =>  PredicateTypeDutch::PROVISIONAL_MOTHER_OF_RAMS,
            PredicateType::PROVISIONAL_PRIME_RAM      =>  PredicateTypeDutch::PROVISIONAL_PRIME_RAM,
        );
    }


    /**
     * @param string $englishString
     * @return string
     */
    public static function getPredicateAbbreviation($englishString)
    {
        return strtr($englishString, self::getEnglishPredicateToAbbreviationArray());
    }

    /**
     * @return array
     */
    public static function getEnglishPredicateToAbbreviationArray()
    {
        return array(
            //PredicateType
            PredicateType::DEFINITIVE_PREMIUM_RAM => 'DP',
            PredicateType::GRADE_RAM => 'K',
            PredicateType::PREFERENT => 'Pref',
            PredicateType::PREFERENT_1 => 'Pref1',
            PredicateType::PREFERENT_2 => 'Pref2',
            PredicateType::PREFERENT_A => 'PrefA',
            PredicateType::PRIME_RAM => 'P',
            PredicateType::MOTHER_OF_RAMS => 'RM',
            PredicateType::STAR_EWE => 'S',
            PredicateType::STAR_EWE_1 => 'S1',
            PredicateType::STAR_EWE_2 => 'S2',
            PredicateType::STAR_EWE_3 => 'S3',
            PredicateType::PROVISIONAL_PRIME_RAM => 'VP',
            PredicateType::PROVISIONAL_MOTHER_OF_RAMS => 'VRM',
        );
    }


    /**
     * @param string $type
     * @return string
     */
    public static function getDutchTreatmentType($type)
    {
        return strtr($type, [
            TreatmentTypeOption::INDIVIDUAL => 'Individueel',
            TreatmentTypeOption::LOCATION => 'UBN',
        ]);
    }


    public static function getReasonOfLossTranslations(TranslatorInterface $translator): array
    {
        return self::getTranslationsFromEnums(ReasonOfLossType::getConstants(), $translator);
    }

    public static function getReasonOfDepartTranslations(TranslatorInterface $translator): array
    {
        return self::getTranslationsFromEnums(ReasonOfDepartType::getConstants(), $translator);
    }

    private static function getTranslationsFromEnums(array $enums, TranslatorInterface $translator): array
    {
        $translations = [];
        foreach ($enums as $enum) {
            $translations[$enum] = $translator->trans($enum);
        }
        return $translations;
    }
}