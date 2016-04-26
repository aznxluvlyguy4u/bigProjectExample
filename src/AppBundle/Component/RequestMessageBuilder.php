<?php

namespace AppBundle\Component;

use AppBundle\Enumerator\RequestType;
use AppBundle\Service\IRSerializer;
use Doctrine\ORM\EntityManager;
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

            case RequestType::DECLARATION_DETAIL_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclarationDetail($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareAnimalFlag($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::DECLARE_ARRIVAL_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareArrival($contentArray);
                $messageObject = $this->arrivalMessageBuilder->buildMessage($messageObjectFrontEnd, $person);
                break;

            case RequestType::DECLARE_BIRTH_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareBirth($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::DECLARE_DEPART_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareDepart($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::DECLARE_EARTAGS_TRANSFER_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareEartagsTransfer($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::DECLARE_LOSS_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareLoss($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::DECLARE_EXPORT_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareExport($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::DECLARE_IMPORT_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseDeclareImport($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::RETRIEVE_EARTAGS_ENTITY:
                $messageObjectFrontEnd = $this->irSerializer->parseRetrieveEartags($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            $messageObject = $messageObjectFrontEnd;
                break;

            case RequestType::REVOKE_DECLARATION_ENTITY:
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