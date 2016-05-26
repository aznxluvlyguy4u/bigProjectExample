<?php

namespace AppBundle\Component;

use AppBundle\Constant\Constant;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Entity\Ram;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Setting\ActionFlagSetting;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class RevokeMessageBuilder
 * @package AppBundle\Component
 */
class RevokeMessageBuilder extends MessageBuilderBase
{
    /**
     * @var Person
     */
    private $person;

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
        $this->em = $em;
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param RevokeDeclaration $messageObject the message received from the front-end
     * @param Person $person
     * @return RevokeDeclaration
     */
    public function buildMessage(RevokeDeclaration $messageObject, $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addRevokeDeclarationData($baseMessageObject, $person);

        return $completeMessageObject;
    }

    /**
     * @param RevokeDeclaration $revokeDeclaration the baseMessageObject
     * @return RevokeDeclaration
     */
    private function addRevokeDeclarationData(RevokeDeclaration $revokeDeclaration, $person)
    {


        $messageNumber = $revokeDeclaration->getMessageNumber();
        $retrievedDeclaration = $this->entityGetter->getRequestMessageByMessageNumber($messageNumber);

        // set both the requestID of the request to revoke AND the requestType of the request to revoke
        //FIXME
        $requestTypeToRevoke = $this->em->getClassMetadata(get_class($retrievedDeclaration))->getName();
        $requestTypeToRevoke = str_replace("AppBundle\\Entity\\", "", $requestTypeToRevoke);

        switch($requestTypeToRevoke) {
            case RequestType::DECLARE_ARRIVAL_ENTITY:
                $revokeDeclaration->setRequestTypeToRevoke(RequestType::DECLARE_ARRIVAL_ENTITY);
                break;
            case RequestType::DECLARE_LOSS_ENTITY:
                $revokeDeclaration->setRequestTypeToRevoke(RequestType::DECLARE_LOSS_ENTITY);
                break;
            case RequestType::DECLARE_BIRTH_ENTITY:
                $revokeDeclaration->setRequestTypeToRevoke(RequestType::DECLARE_BIRTH_ENTITY);
                break;
            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                $revokeDeclaration->setRequestTypeToRevoke(RequestType::DECLARE_ANIMAL_FLAG_ENTITY);
                break;
            case RequestType::DECLARE_DEPART_ENTITY:
                $revokeDeclaration->setRequestTypeToRevoke(RequestType::DECLARE_DEPART_ENTITY);
                break;
            case RequestType::DECLARE_TAGS_TRANSFER_ENTITY:
                $revokeDeclaration->setRequestTypeToRevoke(RequestType::DECLARE_TAGS_TRANSFER_ENTITY);
                break;
            case RequestType::DECLARE_EXPORT_ENTITY:
                $revokeDeclaration->setRequestTypeToRevoke(RequestType::DECLARE_EXPORT_ENTITY);
                break;
            case RequestType::DECLARE_IMPORT_ENTITY:
                $revokeDeclaration->setRequestTypeToRevoke(RequestType::DECLARE_IMPORT_ENTITY);
                break;
            default:
                break;
        }

        //Set values
        $revokeDeclaration->setRequestIdToRevoke($retrievedDeclaration->getRequestId());
        $revokeDeclaration->setRelationNumberKeeper($person->getRelationNumberKeeper());
        $revokeDeclaration->setUbn($retrievedDeclaration->getUbn());
        $revokeDeclaration->setLocation($this->person->getCompanies()[0]->getLocations()[0]);

        //Set related request
        $retrievedDeclaration->setRevoke($revokeDeclaration);

        if(ActionFlagSetting::REVOKE_DECLARATION != null) {
            $revokeDeclaration->setAction(ActionFlagSetting::REVOKE_DECLARATION);
        }

        return $revokeDeclaration;
    }

}