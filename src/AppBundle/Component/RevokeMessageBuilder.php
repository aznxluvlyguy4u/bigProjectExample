<?php

namespace AppBundle\Component;

use AppBundle\Constant\Constant;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Entity\Ram;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Enumerator\RequestType;
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

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
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

        //Set values
        $revokeDeclaration->setRelationNumberKeeper($person->getRelationNumberKeeper());
        $revokeDeclaration->setUbn($retrievedDeclaration->getUbn());
        $revokeDeclaration->setRequestId($retrievedDeclaration->getRequestId());

        //Set related request
        $retrievedDeclaration->setRevoke($revokeDeclaration);

        return $revokeDeclaration;
    }

}