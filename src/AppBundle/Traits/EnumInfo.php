<?php


namespace AppBundle\Traits;


trait EnumInfo
{
    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}