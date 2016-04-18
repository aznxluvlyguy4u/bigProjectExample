<?php

namespace AppBundle\Component;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequestMessageBuilder
 * @package AppBundle\Component
 */
class RequestMessageBuilder
{

    /**
     * @var \JMS\Serializer\Serializer
     */
    private $serializer;

    /**
     * @var ArrivalMessageBuilder
     */
    private $arrivalMessageBuilder;

    public function __construct($serializer)
    {
        $this->serializer = $serializer;
        $this->arrivalMessageBuilder = new ArrivalMessageBuilder($serializer);
    }

    public function build($requestType, $request, $requestId) {

        $message = null;
        switch($requestType) {
            case "DeclareArrival":
                $message = $this->arrivalMessageBuilder->buildMessage($request, $requestId);

                break;
            case " ";
                break;
            default:
                break;
        }

        return $message;
    }
}