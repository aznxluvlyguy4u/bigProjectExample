<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;
use AppBundle\Component\Count;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\LiveStockType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Class MenuBarOutput
 */
class MenuBarOutput extends Output
{

    public static function create(Client $client)
    {
        $result = array(
                  "first_name" => $client->getFirstName(),
                  "last_name" => $client->getLastName(),
                  "ubns" => self::createUbnsArray($client),
                  "email_address" => $client->getEmailAddress()
        );

        return $result;
    }

    public static function createAdmin(Employee $employee)
    {
        $result = array(
            "first_name" => $employee->getFirstName(),
            "last_name" => $employee->getLastName(),
            "email_address" => $employee->getEmailAddress()
        );

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


    public static function createUbnsArray(Client $client)
    {
        $ubns = array();

        foreach($client->getCompanies() as $company) {
            foreach($company->getLocations() as $location) {
                if($location->getIsActive()) {
                    $ubns[] = $location->getUbn();
                }
            }
        }

        return $ubns;
    }

}