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


    /**
     * @param $constantValue
     * @param array $constants
     * @return null|string
     */
    static function getName($constantValue, array $constants = []): ?string
    {
        return array_search(
            $constantValue,
            (empty($constants) ? self::getConstants() : $constants)
        );
    }
}