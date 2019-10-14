<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class ReasonOfLossType
{
    use EnumInfo;

    const NO_REASON = 'NO REASON';
    const ACUTE_DEAD = 'ACUTE DEAD';
    const DISEASE = 'DISEASE';
    const HEAT_CULTIVATE = 'HEAT CULTIVATE';
    const EUTHANASIA = 'EUTHANASIA';
    const OTHER = 'OTHER';
    const ANIMAL_NO_LONGER_PRESENT = 'ANIMAL NO LONGER PRESENT';
}