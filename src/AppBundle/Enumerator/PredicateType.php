<?php


namespace AppBundle\Enumerator;


class PredicateType
{
    const DEFINITIVE_PREMIUM_RAM = 'DEFINITIVE_PREMIUM_RAM';
    const GRADE_RAM = 'GRADE_RAM';
    const PREFERENT = 'PREFERENT';
    const PREFERENT_1 = 'PREFERENT_1';
    const PREFERENT_2 = 'PREFERENT_2';
    const PREFERENT_A = 'PREFERENT_A';
    const PRIME_RAM = 'PRIME_RAM';
    const MOTHER_OF_RAMS = 'MOTHER_OF_RAMS';
    const STAR_EWE = 'STAR_EWE';
    const STAR_EWE_1 = 'STAR_EWE_1';
    const STAR_EWE_2 = 'STAR_EWE_2';
    const STAR_EWE_3 = 'STAR_EWE_3';
    const PROVISIONAL_PRIME_RAM = 'PROVISIONAL_PRIME_RAM';
    const PROVISIONAL_MOTHER_OF_RAMS = 'PROVISIONAL_MOTHER_OF_RAMS';


    /**
     * @return array
     */
    public static function getAll()
    {
        return [
            self::DEFINITIVE_PREMIUM_RAM => self::DEFINITIVE_PREMIUM_RAM,
            self::GRADE_RAM => self::GRADE_RAM,
            self::PREFERENT => self::PREFERENT,
            self::PREFERENT_1 => self::PREFERENT_1,
            self::PREFERENT_2 => self::PREFERENT_2,
            self::PREFERENT_A => self::PREFERENT_A,
            self::PRIME_RAM => self::PRIME_RAM,
            self::MOTHER_OF_RAMS => self::MOTHER_OF_RAMS,
            self::STAR_EWE => self::STAR_EWE,
            self::STAR_EWE_1 => self::STAR_EWE_1,
            self::STAR_EWE_2 => self::STAR_EWE_2,
            self::STAR_EWE_3 => self::STAR_EWE_3,
            self::PROVISIONAL_PRIME_RAM => self::PROVISIONAL_PRIME_RAM,
            self::PROVISIONAL_MOTHER_OF_RAMS => self::PROVISIONAL_MOTHER_OF_RAMS,
        ];
    }
}