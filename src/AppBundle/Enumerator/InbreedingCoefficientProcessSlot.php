<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class InbreedingCoefficientProcessSlot
{
    use EnumInfo;

    const ADMIN = 1;
    const REPORT = 2;
    const SMALL = 3;
}
