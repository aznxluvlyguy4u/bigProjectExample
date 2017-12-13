<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

/**
 * Class FootRot
 * @package AppBundle\Enumerator
 */
class FootRot
{
    use EnumInfo;

    const NOT_SUSPECT_1 = 'NOT SUSPECT LEVEL 1';
    const NOT_SUSPECT_2 = 'NOT SUSPECT LEVEL 2';
    const NOT_SUSPECT_3 = 'NOT SUSPECT LEVEL 3';

    const FREE = 'FREE'; //vrij
    const UNDER_OBSERVATION = 'UNDER OBSERVATION'; //in observatie
    const UNDER_INVESTIGATION = 'UNDER INVESTIGATION'; //in onderzoek
}