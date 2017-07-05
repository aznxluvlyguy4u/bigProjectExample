<?php


namespace AppBundle\Constant;

/**
 * Class BreedIndexTypeConstant
 * @package AppBundle\Constant
 */
class BreedIndexDiscriminatorTypeConstant
{
    const LAMB_MEAT = 'LambMeat';
    const EXTERIOR = 'Exterior';
    const FERTILITY = 'Fertility';
    const WORM_RESISTANCE = 'WormResistance';


    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}