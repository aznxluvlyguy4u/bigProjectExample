<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

/**
 * CL (CLA)
 *
 * Class CaseousLymphadenitisStatus
 * @package AppBundle\Enumerator
 */
class CaseousLymphadenitisStatus
{
    use EnumInfo;

    const FREE_1_YEAR = 'FREE 1 YEAR'; const FREE_2_YEAR = 'FREE 2 YEARS'; //TODO these two are maybe not necessary. Verify.

    const FREE = 'FREE'; //vrij
    const UNDER_OBSERVATION = 'UNDER OBSERVATION'; //in observatie
    const UNDER_INVESTIGATION = 'UNDER INVESTIGATION'; //in onderzoek
    const STATUS_KNOWN_BY_AHD = 'STATUS KNOWN BY ANIMAL HEALTH DEPARTMENT'; //Status bij GD bekend. GD = Gezondheidsdienst voor Dieren. This status is given to locations that are CL free.

}