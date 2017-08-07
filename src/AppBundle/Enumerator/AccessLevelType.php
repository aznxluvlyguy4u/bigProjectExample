<?php

namespace AppBundle\Enumerator;

/**
 * Class ActionType
 * @package AppBundle\Enumerator
 */
class AccessLevelType
{
    const DEVELOPER = 'DEVELOPER';
    const SUPER_ADMIN = 'SUPER_ADMIN';
    const ADMIN = 'ADMIN';

    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}