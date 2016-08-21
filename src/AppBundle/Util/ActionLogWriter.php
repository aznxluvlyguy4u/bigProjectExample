<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\UserActionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Monolog\Handler\Curl\Util;

class ActionLogWriter
{
    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param Location $location
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function declareArrivalOrImportPost(ObjectManager $om, $client, $loggedInUser, $location, $content)
    {
        $isImportAnimal = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::IS_IMPORT_ANIMAL, $content);
        if($isImportAnimal) {
            $userActionType = UserActionType::DECLARE_IMPORT;
            $countryOfOrigin = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::COUNTRY_ORIGIN, $content);
            $origin = 'country of origin: '.$countryOfOrigin;
        } else {
            $userActionType = UserActionType::DECLARE_ARRIVAL;
            $ubnPreviousOwner = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::UBN_PREVIOUS_OWNER, $content);
            $origin = 'ubn previous owner: '.$ubnPreviousOwner;
        }
        $ubn = NullChecker::getUbnFromLocation($location);
        $uln = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content));

        $description = 'ubn destination: '.$ubn.'. '.$origin.'. uln: '.$uln;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        $om->persist($log);
        $om->flush();

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param Location $location
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function declareDepartOrExportPost(ObjectManager $om, $client, $loggedInUser, $location, $content)
    {
        $isExportAnimal = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::IS_EXPORT_ANIMAL, $content);
        if($isExportAnimal) {
            $userActionType = UserActionType::DECLARE_EXPORT;
            $destination = 'export';
        } else {
            $userActionType = UserActionType::DECLARE_DEPART;
            $ubnNewOwner = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::UBN_NEW_OWNER, $content);
            $destination = 'ubn new owner: '.$ubnNewOwner;
        }
        $ubn = NullChecker::getUbnFromLocation($location);
        $uln = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content));

        $description = 'ubn: '.$ubn.'. '.$destination.'. uln: '.$uln;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        $om->persist($log);
        $om->flush();

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param Location $location
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function declareLossPost(ObjectManager $om, $client, $loggedInUser, $location, $content)
    {
        $userActionType = UserActionType::DECLARE_LOSS;

        $ubn = NullChecker::getUbnFromLocation($location);
        $uln = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content));
        $ubnDestructor = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::UBN_DESTRUCTOR, $content);

        $description = 'ubn: '.$ubn.'. ubn destructor: '.$ubnDestructor.'. uln: '.$uln;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        $om->persist($log);
        $om->flush();

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function declareTagTransferPost(ObjectManager $om, $client, $loggedInUser, $content)
    {
        $userActionType = UserActionType::DECLARE_TAGS_TRANSFER;

        $relationNumberAcceptant = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RELATION_NUMBER_ACCEPTANT, $content);
        $ubnNewOwner = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::UBN_NEW_OWNER, $content);
        $tags = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::TAGS, $content);
        $tagsCount = NullChecker::getArrayCount($tags);

        $description = 'rel.nr.acceptant: '.$relationNumberAcceptant.'. ubn new owner: '.$ubnNewOwner.'. tagsCount: '.$tagsCount;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        $om->persist($log);
        $om->flush();

        return $log;
    }

    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function declareTagReplacePost(ObjectManager $om, $client, $loggedInUser, $content)
    {
        $userActionType = UserActionType::DECLARE_TAG_REPLACE;

        $animalArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content);
        $tagArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::TAG, $content);
        $ulnAnimal = NullChecker::getUlnStringFromArray($animalArray);
        $ulnTag = NullChecker::getUlnStringFromArray($tagArray);

        $description = 'uln of animal: '.$ulnAnimal.'. uln of tag: '.$ulnTag.'.';

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        $om->persist($log);
        $om->flush();

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param RevokeDeclaration $revokeDeclaration
     * @return ActionLog
     */
    public static function revokePost(ObjectManager $om, $client, $loggedInUser, $revokeDeclaration)
    {
        $userActionType = UserActionType::REVOKE_DECLARATION;
        $messageNumber = $revokeDeclaration->getMessageNumber();
        $requestTypeToRevoke = $revokeDeclaration->getRequestTypeToRevoke();

        $description = 'revoking: '.$requestTypeToRevoke.' with messageNumber: '.$messageNumber.'.';

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        $om->persist($log);
        $om->flush();

        return $log;
    }

    
    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @return ActionLog
     */
    public static function passwordChange(ObjectManager $om, $client, $loggedInUser)
    {
        $userActionType = UserActionType::USER_PASSWORD_CHANGE;

        $log = new ActionLog($client, $loggedInUser, $userActionType);
        $om->persist($log);
        $om->flush();

        return $log;
    }

    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $emailAddress
     * @return ActionLog
     */
    public static function passwordReset(ObjectManager $om, $client, $emailAddress)
    {
        $userActionType = UserActionType::USER_PASSWORD_RESET;

        $log = new ActionLog($client, $client, $userActionType, false, $emailAddress);
        $om->persist($log);
        $om->flush();

        return $log;
    }
    

    /**
     * @param ObjectManager $om
     * @param ActionLog $log
     * @return ActionLog
     */
    public static function completeActionLog(ObjectManager $om, ActionLog $log)
    {
        $log->setIsCompleted(true);
        $om->persist($log);
        $om->flush();

        return $log;
    }
}