<?php

namespace AppBundle\Component;

use AppBundle\Controller\APIController;
use AppBundle\Entity\Client as Client;
use AppBundle\Entity\DeclareBase as DeclareBase;
use AppBundle\Service\EntityGetter;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class MessageBuilderBaseAPIController
 * @package AppBundle\Controller
 */
class MessageBuilderBase
{

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityGetter
     */
    protected $entityGetter;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->entityGetter = new EntityGetter($em);
    }

    /**
     * Most of the default values are set in the constructor of DeclareBase.
     * Here the values are set for the variables that could not easily
     * be set in the constructor.
     *
     * @param object $messageObject the message received from the front-end as an entity from a class that is extended from DeclareBase.
     * @param Person $person
     * @return object the base message
     */
    protected function buildBaseMessageObject($messageObject, Person $person)
    {
        //Generate new requestId

        if($messageObject->getRequestId()== null) {
            $requestId = $this->getNewRequestId();
            //Add general data to content
            $messageObject->setRequestId($requestId);
        }

        if($messageObject->getAction() == null) {
            $messageObject->setAction("C");
        }

        $messageObject->setLogDate(new \DateTime());
        $messageObject->setRequestState("open");

        if($messageObject->getRecoveryIndicator() == null) {
            $messageObject->setRecoveryIndicator("N");
        }

        //Add relationNumberKeeper to content

        if($person instanceof Client) {
            $relationNumberKeeper = $person->getRelationNumberKeeper();
        } else { //TODO what if an employee does a DA request?
            $relationNumberKeeper = ""; // mandatory for I&R
        }

        $messageObject->setRelationNumberKeeper($relationNumberKeeper);

        return $messageObject;
    }

    /**
     * Generate a pseudo random requestId of MAX length 20
     *
     * @return string
     */
    private function getNewRequestId()
    {
        return uniqid(mt_rand(0,999999));
    }
}