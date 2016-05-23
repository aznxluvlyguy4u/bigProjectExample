<?php

namespace AppBundle\Component;

use AppBundle\Constant\Constant;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\RequestType;
use AppBundle\Service\IRSerializer;
use Doctrine\ORM\EntityManager;
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
     * @var TagSyncMessageBuilder
     */
    private $tagSyncMessageBuilder;

    /**
     * @var TagTransferMessageBuilder
     */
    private $tagTransferMessageBuilder;

    /**
     * @var RetrieveAnimals
     */
    private $retrieveAnimalsMessageBuilder;

    /**
     * @var RetrieveAnimalDetails
     */
    private $retrieveAnimalDetailsBuilder;

    /**
     * @var RetrieveUbnDetailsMessageBuilder
     */
    private $retrieveUbnDetailsBuilder;

    /*
     * @var LossMessageBuilder
     */
    private $lossMessageBuilder;

    /**
     * @var RevokeMessageBuilder
     */
    private $revokeMessageBuilder;

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
        $this->tagSyncMessageBuilder = new TagSyncMessageBuilder($em);
        $this->tagTransferMessageBuilder = new TagTransferMessageBuilder($em);
        $this->lossMessageBuilder = new LossMessageBuilder($em);
        $this->revokeMessageBuilder = new RevokeMessageBuilder($em);
        $this->retrieveAnimalsMessageBuilder = new RetrieveAnimalsMessageBuilder($em);
        $this->retrieveAnimalDetailsBuilder = new RetrieveAnimalDetailsMessageBuilder($em);
        $this->retrieveUbnDetailsBuilder = new RetrieveUbnDetailsMessageBuilder($em);
    }

    /**
     * @param string $messageClassNameSpace
     * @param ArrayCollection $contentArray
     * @param Person|Client $person
     * @param boolean $isEditMessage
     * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails
     * @throws \Exception
     */
    public function build($messageClassNameSpace, ArrayCollection $contentArray, $person, $isEditMessage)
    {
        switch($messageClassNameSpace) {
            case RequestType::DECLARATION_DETAIL_ENTITY:
                $declarationDetail = $this->irSerializer->parseDeclarationDetail($contentArray, $person, $isEditMessage);
                //TODO: only add the mininum required fields for this Message Type
                return $declarationDetail;
            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                $declareAnimalFlag = $this->irSerializer->parseDeclareAnimalFlag($contentArray, $person, $isEditMessage);
                //TODO: only add the mininum required fields for this Message Type
                return $declareAnimalFlag;
            case RequestType::DECLARE_ARRIVAL_ENTITY:
                $declareArrivalRequest = $this->irSerializer->parseDeclareArrival($contentArray, $person, $isEditMessage);
                return $this->arrivalMessageBuilder->buildMessage($declareArrivalRequest, $person);
            case RequestType::DECLARE_BIRTH_ENTITY:
                $declareBirthRequest = $this->irSerializer->parseDeclareBirth($contentArray, $person, $isEditMessage);
                return $this->birthMessageBuilder->buildMessage($declareBirthRequest, $person);
            case RequestType::DECLARE_DEPART_ENTITY:
                $declareDepartRequest = $this->irSerializer->parseDeclareDepart($contentArray, $person, $isEditMessage);
                return $this->departMessageBuilder->buildMessage($declareDepartRequest, $person);
            case RequestType::DECLARE_TAGS_TRANSFER_ENTITY:
                $declareTagsTransferRequest = $this->irSerializer->parseDeclareTagsTransfer($contentArray, $person, $isEditMessage);
                return $this->tagTransferMessageBuilder->buildMessage($declareTagsTransferRequest, $person);
            case RequestType::DECLARE_LOSS_ENTITY:
                $declareLossRequest = $this->irSerializer->parseDeclareLoss($contentArray, $person, $isEditMessage);
                return $this->lossMessageBuilder->buildMessage($declareLossRequest, $person);
            case RequestType::DECLARE_EXPORT_ENTITY:
                $declareExportRequest = $this->irSerializer->parseDeclareExport($contentArray, $person, $isEditMessage);
                return $this->exportMessageBuilder->buildMessage($declareExportRequest, $person);
            case RequestType::DECLARE_IMPORT_ENTITY:
                $declareImportRequest = $this->irSerializer->parseDeclareImport($contentArray, $person, $isEditMessage);
                return $this->importMessageBuilder->buildMessage($declareImportRequest, $person);
            case RequestType::RETRIEVE_TAGS_ENTITY:
                $retrieveTagsRequest = $this->irSerializer->parseRetrieveTags($contentArray, $person, $isEditMessage);
                return $this->tagSyncMessageBuilder->buildMessage($retrieveTagsRequest, $person);
            case RequestType::REVOKE_DECLARATION_ENTITY:
                $revokeDeclaration = new RevokeDeclaration();
                $revokeDeclaration->setMessageNumber($contentArray[Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE]);
                return $this->revokeMessageBuilder->buildMessage($revokeDeclaration, $person);
            case RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY:
                $retrieveAnimalDetailsRequest = $this->irSerializer->parseRetrieveAnimalDetails($contentArray, $person, $isEditMessage);
                return $this->retrieveAnimalDetailsBuilder->buildMessage($retrieveAnimalDetailsRequest, $person);
            case RequestType::RETRIEVE_ANIMALS_ENTITY:
                $retrieveAnimalsRequest = $this->irSerializer->parseRetrieveAnimals($contentArray, $person, $isEditMessage);
                return $this->retrieveAnimalsMessageBuilder->buildMessage($retrieveAnimalsRequest, $person);
            case RequestType::RETRIEVE_COUNTRIES_ENTITY:
                $retrieveCountries = new RetrieveCountries();
                return $retrieveCountries;
            case RequestType::RETRIEVE_UBN_DETAILS_ENTITY:
                $retrieveUbnDetailsRequest = $this->irSerializer->parseRetrieveUbnDetails($contentArray, $person, $isEditMessage);
                return $this->retrieveUbnDetailsBuilder->buildMessage($retrieveUbnDetailsRequest, $person);
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