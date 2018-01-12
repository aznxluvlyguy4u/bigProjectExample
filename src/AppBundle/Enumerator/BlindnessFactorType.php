<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class BlindnessFactorType
{
    use EnumInfo;

    const BLINDNESS_FACTOR_CARRIER = 'BLINDNESS_FACTOR_CARRIER';
    const BLINDNESS_FACTOR_FREE = 'BLINDNESS_FACTOR_FREE';
}