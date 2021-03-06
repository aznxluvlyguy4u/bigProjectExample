<?php


namespace AppBundle\Constant;

/**
 * Class BreedIndexTypeConstant
 * @package AppBundle\Constant
 */
class BreedIndexTypeConstant
{
    const LAMB_MEAT_INDEX = 'VleesLamIndex';
    const EXTERIOR_INDEX = 'ExterieurIndex';
    const FERTILITY_INDEX = 'VruchtbaarheidIndex';
    const WORM_RESISTANCE_INDEX = 'WormResistentieIndex';


    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}