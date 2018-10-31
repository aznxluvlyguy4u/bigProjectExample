<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class AnimalTypeInLatin
{
    use EnumInfo;

    const SHEEP = 'Ovis aries';
    const GOAT = 'Capra aegagrus hircus';

    static function getByDatabaseEnum($enum): ?string {
        switch ($enum) {
            case AnimalType::sheep: return AnimalTypeInLatin::SHEEP;
            case AnimalType::goat: return AnimalTypeInLatin::GOAT;
            default: return null;
        }
    }
}