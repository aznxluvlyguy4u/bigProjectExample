<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class QFeverType
{
    use EnumInfo;

    const BASIC_VACCINATION_FIRST = 'B';
    const BASIC_VACCINATION_SECOND = 'H';
    const REPEATED_VACCINATION = 'H2';
}
