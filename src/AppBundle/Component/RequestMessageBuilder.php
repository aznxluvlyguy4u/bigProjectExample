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
     * @var ArrivalMessageBuilder
     */
    private $arrivalMessageBuilder;

    public function __construct()
    {
        $this->arrivalMessageBuilder = new ArrivalMessageBuilder();
    }

    public function build($requestType, $content, $relationNumberKeeper) {

        $message = null;
        switch($requestType) {
            case "DeclareArrival":
                $content = $this->arrivalMessageBuilder->buildMessage($content, $relationNumberKeeper);
                break;
            case " ";
                break;
            default:
                break;
        }

        return $content;
    }
}