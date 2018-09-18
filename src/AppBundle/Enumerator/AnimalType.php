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
}