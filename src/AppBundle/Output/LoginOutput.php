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
        $emailAddress = $client->getEmailAddress();

        if(true) { //TODO create check here
            $passwordExists = true;
        } else {
            $passwordExists = false;
        }

        $result = array(
                    "ienr" => //TODO DELETE obsolete ienr array only in tandem with frontend
                    array(
                        "username" => $usernameIr,
                        "password" => $passwordExists
                    ),
                    "nsfo" =>
                    array(
                        "username" => $emailAddress
                    )
                );

        return $result;
    }



}