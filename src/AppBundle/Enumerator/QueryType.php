<?php

namespace AppBundle\Enumerator;


/**
 * Class QueryType
 */
class QueryType
{
    const BASE_INSERT = 'BASE_INSERT';
    const INSERT = 'INSERT';
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';
    const SELECT = 'SELECT';

    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}