<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class ScrapieGenotypeSourceType
{
    use EnumInfo;

    const ADMINISTRATIVE = 'ADMINISTRATIVE';
    const ADMIN_EDIT = 'ADMIN_EDIT';
    const LABORATORY_RESEARCH = 'LABORATORY_RESEARCH';
}