<?php

namespace AppBundle\Output;


use AppBundle\Entity\Client;

/**
 * Class LoginOutput
 */
class LoginOutput
{
    /**
     * @param Client $client
     * @return array
     */
    public static function create($client)
    {
        $usernameIr = ""; //TODO update entities to save IenR username and password. NOTE How do you save passwords in the database in a retrievable way?
        $userNameNsfo = $client->getCompanies()->get(0)->getLocations()->get(0)->getUbn(); //TODO Phase 2+ select proper location

        if(true) { //TODO create check here
            $passwordExists = true;
        } else {
            $passwordExists = false;
        }

        $result = array(
                    "ienr" =>
                    array(
                        "username" => $usernameIr,
                        "password" => $passwordExists
                    ),
                    "nsfo" =>
                    array(
                        "username" => $userNameNsfo
                    )
                );

        return $result;
    }



}