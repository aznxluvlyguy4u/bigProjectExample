<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Entity\Client;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ClientOverviewOutput
{
    /**
     * @param array $clients
     * @return array
     */
    public static function createClientsOverview($clients)
    {
        $results = array();

        foreach ($clients as $client) {
            if($client != null) {
                $results[] = self::createClientOverview($client);
            }
        }

        return $results;
    }

    /**
     * @param Client $client
     * @return array
     */
    public static function createClientOverview($client)
    {
        if($client == null) {
            return null;
        }

        $companies = $client->getCompanies();

        $locations = array();
        foreach($companies as $company) {
            foreach($company->getLocations() as $location) {
                $locations[] = $location;
            }
        }

        $res = array("id" => $client->getId(),
            "first_name" => $client->getFirstName(),
            "last_name" => $client->getLastName(),
            "email_address" => $client->getEmailAddress(),
            "is_active" => $client->getIsActive(),
            "username" => $client->getUsername(),
            "cellphone_number" => $client->getCellphoneNumber(),
            "relation_number_keeper" => $client->getRelationNumberKeeper(),
            "companies" => self::createCompaniesOverview($companies),
            "ubns" => self::createUbnOverview($locations)
        );

        return $res;
    }

    /**
     * @param Collection $companies
     * @return array
     */
    private static function createCompaniesOverview($companies)
    {
        $res = array();

        foreach($companies as $company) {
            $res[] = array(
                'id' => $company->getId(),
                'company_name' => $company->getCompanyName()
            );
        }

        return $res;
    }

    /**
     * @param array $locations
     * @return array
     */
    private static function createUbnOverview($locations)
    {
        $res = array();

        foreach($locations as $location) {
            $res[] = $location->getUbn();
        }

        return $res;
    }


}