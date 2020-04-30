<?php

namespace AppBundle\Output;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\Country;
use Doctrine\ORM\EntityManagerInterface;


/**
 * Class MenuBarOutput
 */
class MenuBarOutput extends Output
{

    public static function create(EntityManagerInterface $em, Client $client)
    {
        $result = array(
                  "first_name" => $client->getFirstName(),
                  "last_name" => $client->getLastName(),
                  JsonInputConstant::LOCATIONS => self::createLocationsArray($em, $client),
                  "email_address" => $client->getEmailAddress()
        );

        return $result;
    }

    public static function createAdmin(Employee $employee)
    {
        $result = [
            "first_name" => $employee->getFirstName(),
            "last_name" => $employee->getLastName(),
            "email_address" => $employee->getEmailAddress(),
            "access_level" => $employee->getAccessLevel(),
        ];

        return $result;
    }


    /**
     * @param VwaEmployee $vwaEmployee
     * @return array
     */
    public static function createVwaEmployee(VwaEmployee $vwaEmployee)
    {
        return [
            "first_name" => $vwaEmployee->getFirstName(),
            "last_name" => $vwaEmployee->getLastName(),
            "email_address" => $vwaEmployee->getEmailAddress(),
        ];
    }


    public static function createLocationsArray(EntityManagerInterface $em, Client $client)
    {
        if (!$client || !is_int($client->getId())) {
            return [];
        }

        $sql = "SELECT
                  l.id,  
                  l.ubn,
                  cd.code as ".JsonInputConstant::COUNTRY_CODE.",
                  cd.code = '".Country::NL."' as ".JsonInputConstant::USE_RVO_LOGIC."
                FROM location l
                  INNER JOIN company c ON l.company_id = c.id
                  INNER JOIN address a ON l.address_id = a.id
                  LEFT JOIN country cd ON cd.name = a.country
                WHERE c.is_active AND l.is_active
                  AND c.owner_id = ".$client->getEmployer()->getOwner()->getId();
        return $em->getConnection()->query($sql)->fetchAll();
    }

}