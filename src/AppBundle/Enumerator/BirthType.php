<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class BirthType
{
    use EnumInfo;

    const NO_HELP = 'NO HELP';
    const LIGHT_WITH_HELP = 'LIGHT WITH HELP';
    const NORMAL_WITH_HELP = 'NORMAL WITH HELP';
    const HEAVY_WITH_HELP = 'HEAVY WITH HELP';
    const CAESARIAN_LAMB_TOO_BIG = 'CAESARIAN (LAMB TOO BIG)';
    const CAESARIAN_INSUFFICIENT_ACCESS = 'CAESARIAN (INSUFFICIENT ACCESS)';
}
