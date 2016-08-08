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
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
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
    /** @var ImportMessageBuilder */
    private $importMessageBuilder;

    /** @var ExportMessageBuilder */
    private $exportMessageBuilder;

    /** @var ArrivalMessageBuilder */
    private $arrivalMessageBuilder;

    /** @var BirthMessageBuilder */
    private $birthMessageBuilder;

    /** @var TagSyncMessageBuilder */
    private $tagSyncMessageBuilder;

    /** @var TagTransferMessageBuilder */
    private $tagTransferMessageBuilder;

    /** @var RetrieveAnimals */
    private $retrieveAnimalsMessageBuilder;

    /** @var RetrieveAnimalDetails */
    private $retrieveAnimalDetailsBuilder;

    /** @var RetrieveUbnDetailsMessageBuilder */
    private $retrieveUbnDetailsBuilder;

    /** @var TagReplaceMessageBuilder */
    private $tagReplaceMessageBuilder;

    /** @var LossMessageBuilder */
    private $lossMessageBuilder;

    /** @var RevokeMessageBuilder */
    private $revokeMessageBuilder;

    /** @var IRSerializer */
    private $irSerializer;

    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $currentEnvironment;

    public function __construct($em, $irSerializer, $currentEnvironment)
    {
        $this->entityManager = $em;
        $this->irSerializer = $irSerializer;
        $this->currentEnvironment = $currentEnvironment;
        $this->arrivalMessageBuilder = new ArrivalMessageBuilder($em, $currentEnvironment);
        $this->departMessageBuilder = new DepartMessageBuilder($em, $currentEnvironment);
        $this->importMessageBuilder = new ImportMessageBuilder($em, $currentEnvironment);
        $this->birthMessageBuilder = new BirthMessageBuilder($em, $currentEnvironment);
        $this->exportMessageBuilder = new ExportMessageBuilder($em, $currentEnvironment);
        $this->tagSyncMessageBuilder = new TagSyncMessageBuilder($em, $currentEnvironment);
        $this->tagTransferMessageBuilder = new TagTransferMessageBuilder($em, $currentEnvironment);
        $this->lossMessageBuilder = new LossMessageBuilder($em, $currentEnvironment);
        $this->revokeMessageBuilder = new RevokeMessageBuilder($em, $currentEnvironment);
        $this->retrieveAnimalsMessageBuilder = new RetrieveAnimalsMessageBuilder($em, $currentEnvironment);
        $this->retrieveAnimalDetailsBuilder = new RetrieveAnimalDetailsMessageBuilder($em, $currentEnvironment);
        $this->retrieveUbnDetailsBuilder = new RetrieveUbnDetailsMessageBuilder($em, $currentEnvironment);
        $this->tagReplaceMessageBuilder = new TagReplaceMessageBuilder($em, $currentEnvironment);
    }

    /**
     * @param string $messageClassNameSpace
     * @param ArrayCollection $contentArray
     * @param Person|Client $person
     * @param Location $location
     * @param boolean $isEditMessage
     * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails|DeclareTagReplace
     * @throws \Exception
     */
    public function build($messageClassNameSpace, ArrayCollection $contentArray, $person, $location, $isEditMessage)
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
                if($isEditMessage) { return $declareArrivalRequest; }
                return $this->arrivalMessageBuilder->buildMessage($declareArrivalRequest, $person, $location);
            case RequestType::DECLARE_BIRTH_ENTITY:
                $declareBirthRequest = $this->irSerializer->parseDeclareBirth($contentArray, $person, $isEditMessage);
                return $this->birthMessageBuilder->buildMessage($declareBirthRequest, $person, $location);
            case RequestType::DECLARE_DEPART_ENTITY:
                $declareDepartRequest = $this->irSerializer->parseDeclareDepart($contentArray, $person, $isEditMessage);
                return $this->departMessageBuilder->buildMessage($declareDepartRequest, $person, $location);
            case RequestType::DECLARE_TAGS_TRANSFER_ENTITY:
                $declareTagsTransferRequest = $this->irSerializer->parseDeclareTagsTransfer($contentArray, $person, $isEditMessage);
                return $this->tagTransferMessageBuilder->buildMessage($declareTagsTransferRequest, $person, $location);
            case RequestType::DECLARE_TAG_REPLACE:
                $declareTagReplaceRequest = $this->irSerializer->parseDeclareTagReplace($contentArray, $person, $isEditMessage);
                return $this->tagReplaceMessageBuilder->buildMessage($declareTagReplaceRequest, $person, $location);
            case RequestType::DECLARE_LOSS_ENTITY:
                $declareLossRequest = $this->irSerializer->parseDeclareLoss($contentArray, $person, $isEditMessage);
                return $this->lossMessageBuilder->buildMessage($declareLossRequest, $person, $location);
            case RequestType::DECLARE_EXPORT_ENTITY:
                $declareExportRequest = $this->irSerializer->parseDeclareExport($contentArray, $person, $isEditMessage);
                return $this->exportMessageBuilder->buildMessage($declareExportRequest, $person, $location);
            case RequestType::DECLARE_IMPORT_ENTITY:
                $declareImportRequest = $this->irSerializer->parseDeclareImport($contentArray, $person, $location, $isEditMessage);
                return $this->importMessageBuilder->buildMessage($declareImportRequest, $person, $location);
            case RequestType::RETRIEVE_TAGS_ENTITY:
                $retrieveTagsRequest = $this->irSerializer->parseRetrieveTags($contentArray, $person, $isEditMessage);
                return $this->tagSyncMessageBuilder->buildMessage($retrieveTagsRequest, $person, $location);
            case RequestType::REVOKE_DECLARATION_ENTITY:
                $revokeDeclaration = new RevokeDeclaration();
                $revokeDeclaration->setMessageNumber($contentArray[Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE]);
                return $this->revokeMessageBuilder->buildMessage($revokeDeclaration, $person, $location);
            case RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY:
                $retrieveAnimalDetailsRequest = $this->irSerializer->parseRetrieveAnimalDetails($contentArray, $person, $isEditMessage);
                return $this->retrieveAnimalDetailsBuilder->buildMessage($retrieveAnimalDetailsRequest, $person, $location);
            case RequestType::RETRIEVE_ANIMALS_ENTITY:
                $retrieveAnimalsRequest = $this->irSerializer->parseRetrieveAnimals($contentArray, $person, $isEditMessage);
                return $this->retrieveAnimalsMessageBuilder->buildMessage($retrieveAnimalsRequest, $person, $location);
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