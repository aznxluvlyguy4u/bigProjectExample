<?php


namespace AppBundle\Util;


use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AccessLevelType;

class UnitTestData
{

    /**
     * @param string $testEmail
     * @param string $accessLevel
     * @return Employee
     */
    public static function getTestAdmin($testEmail, $accessLevel = AccessLevelType::ADMIN)
    {
        Validator::validateEmailAddress($testEmail, true);

        $admin = new Employee(
            $accessLevel,
            'JOHN',
            'DOE',
            $testEmail
            );
        return $admin;
    }
}