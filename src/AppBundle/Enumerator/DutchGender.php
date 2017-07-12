<?php


namespace AppBundle\Enumerator;


class DutchGender
{
    const RAM = 'Ram';
    const EWE = 'Ooi';
    const NEUTER = 'Onbekend';

    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}