<?php

namespace AppBundle\Enumerator;

/**
 * Class FootRot
 * @package AppBundle\Enumerator
 */
class FootRot
{
    const NOT_SUSPECT_1 = 'NOT SUSPECT LEVEL 1';
    const NOT_SUSPECT_2 = 'NOT SUSPECT LEVEL 2';
    const NOT_SUSPECT_3 = 'NOT SUSPECT LEVEL 3';

    const FREE = 'FREE'; //vrij
    const UNDER_OBSERVATION = 'UNDER OBSERVATION'; //in observatie
    const UNDER_INVESTIGATION = 'UNDER INVESTIGATION'; //in onderzoek
}