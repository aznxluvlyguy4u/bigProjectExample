<?php

namespace AppBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Client as Client;

/**
 * Class RequestMessageBuilder
 * @package AppBundle\Component
 */
class RequestMessageBuilder
{

    /**
     * @var ArrivalMessageBuilder
     */
    private $arrivalMessageBuilder;

    public function __construct()
    {
        $this->arrivalMessageBuilder = new ArrivalMessageBuilder();
    }

    public function build($requestType, $messageObject, Client $client) {

        $message = null;
        switch($requestType) {
            case "DeclareArrival":
                $content = $this->arrivalMessageBuilder->buildMessage($messageObject, $client);
                break;
            case " ";
                break;
            default:
                break;
        }

        return $content;
    }
}