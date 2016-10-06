<?php

namespace AppBundle\Enumerator;

/**
 * Class AnimalType
 * @package AppBundle\Enumerator
 */
class MeasurementType
{
    const BODY_FAT = 'BodyFat';
    const EXTERIOR = 'Exterior';
    const FAT1 = 'Fat1';
    const FAT2 = 'Fat2';
    const FAT3 = 'Fat3';
    const MUSCLE_THICKNESS = 'MuscleThickness';
    const TAIL_LENGTH = 'TailLength';
    const WEIGHT = 'Weight';


    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::BODY_FAT => self::BODY_FAT,
            self::EXTERIOR => self::EXTERIOR,
            self::FAT1 => self::FAT1,
            self::FAT2 => self::FAT2,
            self::FAT3 => self::FAT3,
            self::MUSCLE_THICKNESS => self::MUSCLE_THICKNESS,
            self::TAIL_LENGTH => self::TAIL_LENGTH,
            self::WEIGHT => self::WEIGHT
        ];
    }
}