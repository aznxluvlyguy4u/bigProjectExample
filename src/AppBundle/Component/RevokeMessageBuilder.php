<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\RevokeDeclaration;
use Doctrine\Common\Persistence\ObjectManager;
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
     * @var ObjectManager
     */
    protected $em;

    public function __construct(ObjectManager $em, $currentEnvironment)
    {
        parent::__construct($em, $currentEnvironment);
        $this->em = $em;
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param RevokeDeclaration $messageObject the message received from the front-end
     * @param Client|Person $person
     * @param Person $loggedInUser
     * @param Location $location
     * @return RevokeDeclaration
     */
    public function buildMessage(RevokeDeclaration $messageObject, $person, $loggedInUser, $location)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person, $loggedInUser);
        $completeMessageObject = $this->addRevokeDeclarationData($baseMessageObject, $person, $location);

        return $completeMessageObject;
    }

    /**
     * @param RevokeDeclaration $revokeDeclaration the baseMessageObject
     * @param Client|Person $person
     * @param Location $location
     * @return RevokeDeclaration
     */
    private function addRevokeDeclarationData(RevokeDeclaration $revokeDeclaration, $person, $location)
    {


        $messageNumber = $revokeDeclaration->getMessageNumber();
        $retrievedDeclaration = $this->entityGetter->getRequestMessageByMessageNumber($messageNumber);

        //Set values
        $revokeDeclaration->setRequestTypeToRevoke(Utils::getClassName($retrievedDeclaration));
        $revokeDeclaration->setRequestIdToRevoke($retrievedDeclaration->getRequestId());

        $revokeDeclaration->setRelationNumberKeeper($person->getRelationNumberKeeper());
        $revokeDeclaration->setUbn($retrievedDeclaration->getUbn());
        $revokeDeclaration->setLocation($location);

        //Set related request
        $retrievedDeclaration->setRevoke($revokeDeclaration);

        return $revokeDeclaration;
    }

}