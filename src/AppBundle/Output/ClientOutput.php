<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 20-4-17
 * Time: 10:30
 */

namespace AppBundle\Output;


use AppBundle\Entity\Client;

class ClientOutput
{
    public static function createOwnerOutput(Client $client){
        return array(
            'person_id' => $client->getPersonId(),
            'prefix' => $client->getPrefix(),
            'last_name' => $client->getLastName(),
            'first_name' => $client->getFirstName(),
            'email_address' => $client->getEmailAddress(),
        );
    }
}