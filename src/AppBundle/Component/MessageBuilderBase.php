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
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
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
        $this->actionType = self::getActionTypeByEnvironment($currentEnvironment);
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
     * @return DeclareBase|DeclareArrival|DeclareAnimalFlag|DeclareBirth|DeclareDepart|DeclareExport|DeclareImport|DeclareLoss|DeclareTagsTransfer|Mate|DeclarationDetail|RevokeDeclaration|DeclareTagReplace the base message
     */
    protected function buildBaseMessageObject($messageObject, Person $person, Person $loggedInUser,
                                              Location $location)
    {
        $messageObject = self::setStandardDeclareBaseValues($messageObject, $person, $loggedInUser, $this->actionType, $location->isDutchLocation());

        if($messageObject->getAction() == null) {
            $messageObject->setAction($this->actionType);
        }

        if($messageObject->getRecoveryIndicator() == null) {
            $messageObject->setRecoveryIndicator(RecoveryIndicatorType::N);
        }

        return $messageObject;
    }

    /**
     *
     * @param object $messageObject the message received
     * @param Client $client
     * @param Person $loggedInUser
     * @return RetrieveUbnDetails|RetrieveAnimals|RetrieveAnimalDetails|RetrieveTags|RetrieveCountries the retrieve message
     */
    protected function buildBaseRetrieveMessageObject($messageObject, $client, $loggedInUser)
    {
        return self::setStandardBaseRetrieveValues($messageObject, $client, $loggedInUser);
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


    /**
     * @return string
     */
    public static function getRandomNonRvoMessageNumber():string
    {
        return substr(self::getNewRequestId(),0,RevokeDeclaration::MESSAGE_NUMBER_MAX_COUNT);
    }


    /**
     * @param DeclareBase $declare
     * @param Client $client
     * @param Person $loggedInUser
     * @param string $actionType
     * @param bool $isRvoMessage
     * @return DeclareBase|DeclareArrival|DeclareAnimalFlag|DeclareBirth|DeclareDepart|DeclareExport|DeclareImport|DeclareLoss|DeclareTagsTransfer|Mate|DeclarationDetail|RevokeDeclaration|DeclareTagReplace the base message
     */
    public static function setStandardDeclareBaseValues($declare, $client, $loggedInUser, $actionType, $isRvoMessage)
    {
        $declare = self::setDeclareValuesBase($declare, $client, $loggedInUser);

        if($declare->getAction() == null) {
            $declare->setAction($actionType);
        }

        if($declare->getRecoveryIndicator() == null) {
            $declare->setRecoveryIndicator(RecoveryIndicatorType::N);
        }

        $declare->setIsRvoMessage($isRvoMessage);

        return $declare;
    }


    /**
     * @param DeclareBase $declare
     * @param Client $client
     * @param Person $loggedInUser
     * @return DeclareBase
     */
    public static function setStandardBaseRetrieveValues($declare, $client, $loggedInUser)
    {
        return self::setDeclareValuesBase($declare, $client, $loggedInUser);
    }


    /**
     * @param DeclareBase $declare
     * @param Client $client
     * @param Person $loggedInUser
     * @return DeclareBase
     */
    private static function setDeclareValuesBase($declare, $client, $loggedInUser)
    {
        if ($declare->getRequestId()== null) {
            $declare->setRequestId(self::getNewRequestId());
        }

        $declare->setLogDate(new \DateTime());
        $declare->setRequestState(RequestStateType::OPEN);

        $relationNumberKeeper = ($client instanceof Client) ? $client->getRelationNumberKeeper() : null;
        $declare->setRelationNumberKeeper($relationNumberKeeper);

        if($loggedInUser instanceof Person) {
            $declare->setActionBy($loggedInUser);
        }

        return $declare;
    }


    /**
     * @param string $environment
     * @return string
     */
    public static function getActionTypeByEnvironment($environment): string
    {
        if (
            $environment === Environment::PROD ||
            $environment === Environment::DEV ||
            $environment === Environment::LOCAL
        ) {
            return ActionType::V_MUTATE;
        }

        if ($environment === Environment::TEST) {
            return ActionType::C_READ_ONLY;
        }

        return ActionType::V_MUTATE;
    }
}