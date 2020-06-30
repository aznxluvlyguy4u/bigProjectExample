<?php


namespace AppBundle\Constant;

use AppBundle\Traits\EnumInfo;

/**
 * Class MixBlupBreedValueType
 * @package AppBundle\Enumerator
 */
class MixBlupAnalysis
{
    use EnumInfo;

    //Exterior
    const EXTERIOR_LEG_WORK = 'ExtBeenw';
    const EXTERIOR_MUSCULARITY = 'ExtBesp';
    const EXTERIOR_PROPORTION = 'ExtEvenr';
    const EXTERIOR_SKULL = 'ExtKop';
    const EXTERIOR_PROGRESS = 'ExtOntw';
    const EXTERIOR_TYPE = 'ExtType';

    //Lamb Meat
    const LAMB_MEAT = 'Vleeslam';
    const TAIL_LENGTH = 'StaartLen';

    //Reproduction
    const BIRTH_PROGRESS = 'Geboorte';
    const FERTILITY = 'Vruchtb';
    const FERTILITY_1 = 'Vruchtb1';
    const FERTILITY_2 = 'Vruchtb2';
    const FERTILITY_3 = 'Vruchtb3';
    const FERTILITY_4 = 'Vruchtb4';

    //Worm Resistance
    const WORM_RESISTANCE = 'WormRes';
}