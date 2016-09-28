<?php

namespace AppBundle\Enumerator;

/**
 * Class AnimalType
 * @package AppBundle\Enumerator
 */
class PersonType
{
    const CLIENT = 'Client';
    const EMPLOYEE = 'Employee';
    const INSPECTOR = 'Inspector';

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::CLIENT => self::CLIENT,
            self::EMPLOYEE => self::EMPLOYEE,
            self::INSPECTOR => self::INSPECTOR
        ];
    }


    /**
     * @param string $type
     * @return bool
     */
    public static function isValidType($type)
    {
        return array_key_exists($type, self::getTypes());
    }
}