<?php

namespace AppBundle\Component;

use AppBundle\Service\IRSerializer;
use Doctrine\ORM\EntityManager;
use AppBundle\Enumerator\MessageClass;
use AppBundle\Entity\Client as Client;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

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

    /**
     * @var IRSerializer
     */
    private $irSerializer;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct($em, $irSerializer)
    {
        $this->entityManager = $em;
        $this->irSerializer = $irSerializer;

        $this->arrivalMessageBuilder = new ArrivalMessageBuilder($em);
    }

    public function build($messageClassNameSpace, ArrayCollection $contentArray, Person $person)
    {
        $messageObject = null;
        $message = null;
        switch($messageClassNameSpace) {

            case MessageClass::DeclarationDetail:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclarationDetail($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::DeclareAnimalFlag:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareAnimalFlag($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::DeclareArrival:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareArrival($contentArray);
                $messageObject = $this->arrivalMessageBuilder->buildMessage($messageObjectFrontEnd, $person);
                break;

            case MessageClass::DeclareBirth:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareBirth($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::DeclareDepart:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareDepart($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::DeclareEartagsTransfer:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareEartagsTransfer($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::DeclareLoss:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareLoss($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::DeclareExport:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareExport($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::DeclareImport:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareImport($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::RetrieveEartags:
                $messageObjectFrontEnd = $this->irSerializer->parseRetrieveEartags($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            case MessageClass::RevokeDeclaration:
                $messageObjectFrontEnd = $this->irSerializer->parseRevokeDeclaration($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            default:
                break;
        }

        return $messageObject;
    }
}