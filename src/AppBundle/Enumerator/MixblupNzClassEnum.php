<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class MixblupNzClassEnum
{
    use EnumInfo;

    const NONE_DETECTED = 'NONE DETECTED';
    const MEDIUM = 'MEDIUM';
    const TRACE = 'TRACE';
    const LOW = 'LOW';
    const HIGH = 'HIGH';
}