<?php


namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

/**
 * Class MixBlupType
 * @package AppBundle\Enumerator
 */
class MixBlupType
{
    use EnumInfo;

    const LAMB_MEAT_INDEX = 'Vleeslam';
    const FERTILITY = 'Vruchtb';
    const WORM = 'Worm';
    const EXTERIOR = 'Exterieur';
}