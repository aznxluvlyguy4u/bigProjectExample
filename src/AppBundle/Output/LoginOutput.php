<?php

namespace AppBundle\Output;


use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;

/**
 * Class LoginOutput
 */
class LoginOutput
{
    /**
     * @param Client $client
     * @param Person|Employee $loggedInUser
     * @return array
     */
    public static function create($client, $loggedInUser, $revealHistoricAnimals)
    {
        $result = array(
                    "nsfo" =>
                    array(
                        "username" => $client->getEmailAddress(),
                        "logged_in_user" => [
                            'first_name' => $loggedInUser->getFirstName(),
                            'last_name' => $loggedInUser->getLastName(),
                            'public_live_stock' => $revealHistoricAnimals,
                            'access_level' => $loggedInUser instanceof Employee ? $loggedInUser->getAccessLevel() : null,
                        ]
                    )
                );

        return $result;
    }



}