<?php

namespace AppBundle\Enumerator;


class BreedTrait
{
    const FAT = 'FAT';
    const GROWTH = 'GROWTH';
    const MUSCLE_THICKNESS = 'MUSCLE_THICKNESS';


    /**
     * @return array
     */
    public static function getAll()
    {
        return [
          self::FAT => self::FAT,
          self::GROWTH => self::GROWTH,
          self::MUSCLE_THICKNESS => self::MUSCLE_THICKNESS
        ];
    }


    /**
     * @param $trait
     * @return bool
     */
    public static function contains($trait)
    {
        return array_key_exists($trait, self::getAll());
    }
}