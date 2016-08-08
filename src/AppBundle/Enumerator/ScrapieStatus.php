<?php

namespace AppBundle\Enumerator;

/**
 * Class ScrapieStatus
 * @package AppBundle\Enumerator
 */
class ScrapieStatus
{
    const RESISTANT = 'RESISTANT'; //All animals on the location have the genotype ARR/ARR. This is the highest health level

    const FREE = 'FREE'; //vrij
    const UNDER_OBSERVATION = 'UNDER OBSERVATION'; //in observatie
    const UNDER_INVESTIGATION = 'UNDER INVESTIGATION'; //in onderzoek
}