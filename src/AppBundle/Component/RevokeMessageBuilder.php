<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\RevokeDeclaration;
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
     * @var Client|Person
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
     * @param Client|Person $person
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
     * @param Client|Person $person
     * @return RevokeDeclaration
     */
    private function addRevokeDeclarationData(RevokeDeclaration $revokeDeclaration, $person)
    {


        $messageNumber = $revokeDeclaration->getMessageNumber();
        $retrievedDeclaration = $this->entityGetter->getRequestMessageByMessageNumber($messageNumber);

        //Set values
        $revokeDeclaration->setRequestTypeToRevoke(Utils::getClassName($retrievedDeclaration));
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