<?php

namespace AppBundle\Component;

use AppBundle\Constant\Environment;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareMate;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\Client as Client;
use AppBundle\Enumerator\ActionType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Service\EntityGetter;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class MessageBuilderBaseAPIController
 * @package AppBundle\Controller
 */
class MessageBuilderBase
{

    /** @var ObjectManager */
    protected $em;

    /** @var EntityGetter */
    protected $entityGetter;

    /** @var string */
    protected $actionType;


    /**
     * MessageBuilderBase constructor.
     * @param ObjectManager $em
     * @param string $currentEnvironment
     */
    public function __construct(ObjectManager $em, $currentEnvironment = null)
    {
        $this->em = $em;
        $this->entityGetter = new EntityGetter($em);
        
        /* Set actionType based on environment */
        switch($currentEnvironment) {
            case Environment::PROD:
                $this->actionType = ActionType::V_MUTATE;
                break;
            case Environment::DEV:
                $this->actionType = ActionType::V_MUTATE;
                break;
            case Environment::TEST:
                $this->actionType = ActionType::C_READ_ONLY;
                break;
            case Environment::LOCAL:
                $this->actionType = ActionType::V_MUTATE;
                break;
            default; //dev
                $this->actionType = ActionType::V_MUTATE;
                break;
        }
    }

    /**
     * Most of the default values are set in the constructor of DeclareBase.
     * Here the values are set for the variables that could not easily
     * be set in the constructor.
     *
     * @param DeclareBase $messageObject the message received from the front-end as an entity from a class that is extended from DeclareBase.
     * @param Person $person
     * @param Person $loggedInUser
     * @param Location $location
     * @return DeclareBase|DeclareArrival|DeclareAnimalFlag|DeclareBirth|DeclareDepart|DeclareExport|DeclareImport|DeclareLoss|DeclareTagsTransfer|DeclareMate|DeclarationDetail|RevokeDeclaration|DeclareTagReplace the base message
     */
    protected function buildBaseMessageObject($messageObject, Person $person, Person $loggedInUser,
                                              Location $location)
    {
        //Generate new requestId

        if($messageObject->getRequestId()== null) {
            $requestId = $this->getNewRequestId();
            //Add general data to content
            $messageObject->setRequestId($requestId);
        }

        if($messageObject->getAction() == null) {
            $messageObject->setAction($this->actionType);
        }

        $messageObject->setLogDate(new \DateTime());
        $messageObject->setRequestState(RequestStateType::OPEN);

        if($messageObject->getRecoveryIndicator() == null) {
            $messageObject->setRecoveryIndicator(RecoveryIndicatorType::N);
        }

        //Add relationNumberKeeper to content

        if($person instanceof Client) {
            $relationNumberKeeper = $person->getRelationNumberKeeper();
        } else { //TODO what if an employee does a DA request?
            $relationNumberKeeper = ""; // mandatory for I&R
        }

        $messageObject->setRelationNumberKeeper($relationNumberKeeper);

        if($loggedInUser instanceof Person) {
            $messageObject->setActionBy($loggedInUser);
        }

        $messageObject->setIsRvoMessage($location->isDutchLocation());

        return $messageObject;
    }

    /**
     *
     * @param object $messageObject the message received
     * @param Person $person
     * @param Person $loggedInUser
     * @return RetrieveUbnDetails|RetrieveAnimals|RetrieveAnimalDetails|RetrieveTags|RetrieveCountries the retrieve message
     */
    protected function buildBaseRetrieveMessageObject($messageObject, $person, $loggedInUser)
    {
        //Generate new requestId

        if($messageObject->getRequestId()== null) {
            $requestId = $this->getNewRequestId();
            //Add general data to content
            $messageObject->setRequestId($requestId);
        }

        $messageObject->setLogDate(new \DateTime());
        $messageObject->setRequestState(RequestStateType::OPEN);

        //Add relationNumberKeeper to content

        if($person instanceof Client) {
            $relationNumberKeeper = $person->getRelationNumberKeeper();
        } else { //TODO what if an employee does a DA request?
            $relationNumberKeeper = ""; // mandatory for I&R
        }

        $messageObject->setRelationNumberKeeper($relationNumberKeeper);

        if($loggedInUser instanceof Person) {
            $messageObject->setActionBy($loggedInUser);
        }

        return $messageObject;
    }

    /**
     * Generate a pseudo random requestId of MAX length 20
     *
     * @return string
     */
    public static function getNewRequestId()
    {
        return uniqid(mt_rand(0,9999999));
    }

}