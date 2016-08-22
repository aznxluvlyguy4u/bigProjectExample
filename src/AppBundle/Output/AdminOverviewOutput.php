<?php

namespace AppBundle\Output;

use AppBundle\Entity\Employee;

class AdminOverviewOutput
{
    /**
     * @param array $admins
     * @return array
     */
    public static function createAdminsOverview($admins)
    {
        $results = array();

        foreach ($admins as $admin) {
            if($admin != null) {
                $results[] = self::createAdminOverview($admin);
            }
        }

        return $results;
    }

    /**
     * @param Employee $admin
     * @return array
     */
    public static function createAdminOverview($admin)
    {
        if($admin == null) {
            return null;
        }

        $res = array(
            "person_id" => $admin->getPersonId(),
            "prefix" => $admin->getPrefix(),
            "first_name" => $admin->getFirstName(),
            "last_name" => $admin->getLastName(),
            "email_address" => $admin->getEmailAddress(),
            "access_level" => $admin->getAccessLevel()
        );

        return $res;
    }

}