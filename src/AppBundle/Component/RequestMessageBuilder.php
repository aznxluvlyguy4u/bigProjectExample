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
        $message = null;
        switch($messageClassNameSpace) {

            case RequestType::DECLARATION_DETAIL_ENTITY:
                $declarationDetail = $this->irSerializer->parseDeclarationDetail($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                return $declarationDetail;
                
            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                $declareAnimalFlag = $this->irSerializer->parseDeclareAnimalFlag($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                return $declareAnimalFlag;
                
            case RequestType::DECLARE_ARRIVAL_ENTITY:
                $declareArrivalRequest = $this->irSerializer->parseDeclareArrival($contentArray);
                return $this->arrivalMessageBuilder->buildMessage($declareArrivalRequest, $person);
                
            case RequestType::DECLARE_BIRTH_ENTITY:
                $declareBirth = $this->irSerializer->parseDeclareBirth($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                return $declareBirth;
                
            case RequestType::DECLARE_DEPART_ENTITY:
                $declareDepart = $this->irSerializer->parseDeclareDepart($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                return $declareDepart;
                
            case RequestType::DECLARE_EARTAGS_TRANSFER_ENTITY:
                $declareEartagsTransfer = $this->irSerializer->parseDeclareEartagsTransfer($contentArray);
                //TODO: only add the mininum required fields for this Message Type
                return $declareEartagsTransfer;
                
            case RequestType::DECLARE_LOSS_ENTITY:
                $declareLoss = $this->irSerializer->parseDeclareLoss($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            return $declareLoss;
                
            case RequestType::DECLARE_EXPORT_ENTITY:
                $declareExport = $this->irSerializer->parseDeclareExport($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            return $declareExport;
                
            case RequestType::DECLARE_IMPORT_ENTITY:
                $declareImport = $this->irSerializer->parseDeclareImport($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            return $declareImport;
                
            case RequestType::RETRIEVE_EARTAGS_ENTITY:
                $retrieveEartags = $this->irSerializer->parseRetrieveEartags($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            return $retrieveEartags;
                
            case RequestType::REVOKE_DECLARATION_ENTITY:
                $revokeDeclaration = $this->irSerializer->parseRevokeDeclaration($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            return $revokeDeclaration;
                
            default:
                return null;
                
        }
    }
}