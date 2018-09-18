<?php

namespace AppBundle\Enumerator;
use AppBundle\Traits\EnumInfo;

/**
 * Class AnimalType
 * @package AppBundle\Enumerator
 */
class AnimalType
{
    use EnumInfo;

    const sheep = 3;
    const goat = 4;

    static function getByDatabaseEnum($enum): ?string {
        switch ($enum) {
            case AnimalType::sheep: return 'SHEEP';
            case AnimalType::goat: return 'GOAT';
            default: return null;
        }
    }
}