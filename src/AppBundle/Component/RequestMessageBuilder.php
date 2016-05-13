<?php

namespace AppBundle\Component;

use AppBundle\Entity\RetrieveEartags;
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
     * @var ImportMessageBuilder
     */
    private $importMessageBuilder;

    /**
     * @var ExportMessageBuilder
     */
    private $exportMessageBuilder;

    /**
     * @var ArrivalMessageBuilder
     */
    private $arrivalMessageBuilder;

    /**
     * @var BirthMessageBuilder
     */
    private $birthMessageBuilder;

    /**
     * @var RetrieveEartagsMessageBuilder
     */
    private $retrieveEartagsMessageBuilder;

    /**
     * @var TagTransferMessageBuilder
     */
    private $tagTransferMessageBuilder;

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
        $this->departMessageBuilder = new DepartMessageBuilder($em);
        $this->importMessageBuilder = new ImportMessageBuilder($em);
        $this->birthMessageBuilder = new BirthMessageBuilder($em);
        $this->exportMessageBuilder = new ExportMessageBuilder($em);
        $this->retrieveEartagsMessageBuilder = new RetrieveEartagsMessageBuilder($em);
        $this->tagTransferMessageBuilder = new TagTransferMessageBuilder($em);
    }

    public function build($messageClassNameSpace, ArrayCollection $contentArray, Person $person, $isEditMessage)
    {
        switch($messageClassNameSpace) {
            case RequestType::DECLARATION_DETAIL_ENTITY:
                $declarationDetail = $this->irSerializer->parseDeclarationDetail($contentArray, $isEditMessage);
                //TODO: only add the mininum required fields for this Message Type
                return $declarationDetail;
            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                $declareAnimalFlag = $this->irSerializer->parseDeclareAnimalFlag($contentArray, $isEditMessage);
                //TODO: only add the mininum required fields for this Message Type
                return $declareAnimalFlag;
            case RequestType::DECLARE_ARRIVAL_ENTITY:
                $declareArrivalRequest = $this->irSerializer->parseDeclareArrival($contentArray, $isEditMessage);
                return $this->arrivalMessageBuilder->buildMessage($declareArrivalRequest, $person);
            case RequestType::DECLARE_BIRTH_ENTITY:
                $declareBirthRequest = $this->irSerializer->parseDeclareBirth($contentArray, $isEditMessage);
                return $this->birthMessageBuilder->buildMessage($declareBirthRequest, $person);
            case RequestType::DECLARE_DEPART_ENTITY:
                $declareDepartRequest = $this->irSerializer->parseDeclareDepart($contentArray, $isEditMessage);
                return $this->departMessageBuilder->buildMessage($declareDepartRequest, $person);
            case RequestType::DECLARE_EARTAGS_TRANSFER_ENTITY:
                $declareTagsTransferRequest = $this->irSerializer->parseDeclareEartagsTransfer($contentArray, $isEditMessage);
                return $this->tagTransferMessageBuilder->buildMessage($declareTagsTransferRequest, $person);
            case RequestType::DECLARE_LOSS_ENTITY:
                $declareLoss = $this->irSerializer->parseDeclareLoss($contentArray, $isEditMessage);
                //TODO: only add the mininum required fields for this Message Type
                return $declareLoss;
            case RequestType::DECLARE_EXPORT_ENTITY:
                $declareExportRequest = $this->irSerializer->parseDeclareExport($contentArray, $isEditMessage);
                return $this->exportMessageBuilder->buildMessage($declareExportRequest, $person);
            case RequestType::DECLARE_IMPORT_ENTITY:
                $declareImportRequest = $this->irSerializer->parseDeclareImport($contentArray, $isEditMessage);
                return $this->importMessageBuilder->buildMessage($declareImportRequest, $person);
            case RequestType::RETRIEVE_EARTAGS_ENTITY:
                $retrieveEartagsRequest = $this->irSerializer->parseRetrieveEartags($contentArray, $isEditMessage);
                return $this->retrieveEartagsMessageBuilder->buildMessage($retrieveEartagsRequest, $person);
            case RequestType::REVOKE_DECLARATION_ENTITY:
                $revokeDeclaration = $this->irSerializer->parseRevokeDeclaration($contentArray, $isEditMessage);
                //TODO: only add the mininum required fields for this Message Type
                return $revokeDeclaration;
            default:
                if ($messageClassNameSpace == null){
                    throw new \Exception('Cannot pass null into the RequestMessageBuilder');
                } else {
                    throw new \Exception('No valid message class passed into the RequestMessageBuilder');
                }
                break;
        }
    }
}