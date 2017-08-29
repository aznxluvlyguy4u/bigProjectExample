<?php


namespace AppBundle\Enumerator;

/**
 * Class TreatmentTypeOption
 */
class TreatmentTypeOption
{
    const LOCATION = 'LOCATION';
    const INDIVIDUAL = 'INDIVIDUAL';

    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}