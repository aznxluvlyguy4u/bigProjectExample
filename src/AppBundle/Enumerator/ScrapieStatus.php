<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

/**
 * Class ScrapieStatus
 * @package AppBundle\Enumerator
 */
class ScrapieStatus
{
    use EnumInfo;

    const RESISTANT = 'RESISTANT'; //All animals on the location have the genotype ARR/ARR. This is the highest health level

    const FREE = 'FREE'; //vrij
    const UNDER_OBSERVATION = 'UNDER OBSERVATION'; //in observatie
    const UNDER_INVESTIGATION = 'UNDER INVESTIGATION'; //in onderzoek
}