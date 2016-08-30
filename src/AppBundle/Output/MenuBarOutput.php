<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;
use AppBundle\Component\Count;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
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