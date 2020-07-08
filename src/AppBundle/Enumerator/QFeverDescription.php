<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class QFeverDescription
{
    use EnumInfo;

    const BASIC_VACCINATION_FIRST = 'Basisvaccinatie: 1e vaccinatie';
    const BASIC_VACCINATION_SECOND = 'Basisvaccinatie: 2e vaccinatie';
    const REPEATED_VACCINATION = 'Herhalingsvaccinatie';
}
