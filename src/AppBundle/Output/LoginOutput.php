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
        $result = array(
                    "nsfo" =>
                    array(
                        "username" => $client->getEmailAddress()
                    )
                );

        return $result;
    }



}