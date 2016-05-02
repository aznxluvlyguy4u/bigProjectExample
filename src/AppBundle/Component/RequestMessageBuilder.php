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
     * @var ImportMessageBuilder
     */
    private $importMessageBuilder;

    /**
     * @var ImportMessageBuilder
     */
    private $birthMessageBuilder;

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
        $this->importMessageBuilder = new ImportMessageBuilder($em);
        $this->birthMessageBuilder = new BirthMessageBuilder($em);
    }

    public function build($messageClassNameSpace, ArrayCollection $contentArray, Person $person)
    {
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
                $declareBirthRequest = $this->irSerializer->parseDeclareBirth($contentArray);
                return $this->birthMessageBuilder->buildMessage($declareBirthRequest, $person);
                
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
                $declareImportRequest = $this->irSerializer->parseDeclareImport($contentArray);
                return $this->importMessageBuilder->buildMessage($declareImportRequest, $person);

            case RequestType::RETRIEVE_EARTAGS_ENTITY:
                $retrieveEartags = $this->irSerializer->parseRetrieveEartags($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            return $retrieveEartags;
                
            case RequestType::REVOKE_DECLARATION_ENTITY:
                $revokeDeclaration = $this->irSerializer->parseRevokeDeclaration($contentArray);
                //TODO: only add the mininum required fields for this Message Type
            return $revokeDeclaration;
                
            default:
                if ($messageClassNameSpace == null){
                    throw new \Exception('Cannot pass null into the RequestMessageBuilder');
                } else {
                    throw new \Exception('No valid message class passed into the RequestMessageBuilder');
                }
                
        }
    }
}