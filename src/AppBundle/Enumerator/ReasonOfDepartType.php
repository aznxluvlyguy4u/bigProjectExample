<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class ReasonOfDepartType
{
    use EnumInfo;

    const NO_REASON = 'NO REASON';
    const BREEDING_FARM = 'BREEDING/FARM';
    const RENT = 'RENT';
    const SLAUGHTER_MATURE = 'SLAUGHTER MATURE';
    const SLAUGHTER_UDDER = 'SLAUGHTER: UDDER';
    const SLAUGHTER_LEGS = 'SLAUGHTER: LEGS';
    const SLAUGHTER_FOOTROT = 'SLAUGHTER: FOOTROT';
    const SLAUGHTER_FERTILITY = 'SLAUGHTER: FERTILITY';
    const SLAUGHTER_GUST = 'SLAUGHTER: GUST';
    const SLAUGHTER_DENTAL = 'SLAUGHTER: DENTAL';
    const OTHER = 'OTHER';
}