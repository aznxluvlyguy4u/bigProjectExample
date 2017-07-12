<?php

namespace AppBundle\Enumerator;


/**
 * Class TexelaarPedigreeRegisterAbbreviation
 * @package AppBundle\Enumerator
 */
class TexelaarPedigreeRegisterAbbreviation
{
    const BT = 'BT';
    const DK = 'DK';
    const NTS = 'NTS';
    const TES = 'TES';
    const TSNH = 'TSNH';


    /**
     * @return array
     */
    public static function getAll()
    {
        return [
            self::BT => self::BT,
            self::DK => self::DK,
            self::NTS => self::NTS,
            self::TES => self::TES,
            self::TSNH => self::TSNH,
        ];
    }
}