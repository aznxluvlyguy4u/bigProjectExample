<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;
use AppBundle\Component\Count;
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
                  "ubns" => self::createUbnsArray($client)
        );

        return $result;
    }


    public static function createUbnsArray(Client $client)
    {
        $ubns = array();

        foreach($client->getCompanies() as $company) {
            foreach($company->getLocations() as $location) {
                $ubns[] = $location->getUbn();
            }
        }

        return $ubns;
    }

}