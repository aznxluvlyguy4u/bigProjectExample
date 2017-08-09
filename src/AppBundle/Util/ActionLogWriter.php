<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\UserActionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

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
        DoctrineUtil::persistAndFlush($om, $log);

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
        DoctrineUtil::persistAndFlush($om, $log);

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
        $ubnProcessor = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::UBN_PROCESSOR, $content);

        $description = 'ubn: '.$ubn.'. ubn processor: '.$ubnProcessor.'. uln: '.$uln;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        DoctrineUtil::persistAndFlush($om, $log);

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
        DoctrineUtil::persistAndFlush($om, $log);

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
        DoctrineUtil::persistAndFlush($om, $log);

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
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Person $loggedInUser
     * @param Client $client
     * @param Company $company
     * @return ActionLog
     */
    public static function updateProfile(ObjectManager $om, $client, $loggedInUser, $company)
    {
        $userActionType = UserActionType::PROFILE_UPDATE;
        $description = 'companyId: '.$company->getId().', companyName: '.$company->getCompanyName().'.';
        $log = new ActionLog($company->getOwner(), $loggedInUser, $userActionType, true, $description);
        $om->persist($client);
        $om->persist($loggedInUser);
        $om->persist($log);

        return $log;
    }

    /**
     * @param ObjectManager $om
     * @param Person $loggedInUser
     * @param Client $client
     * @param string $description
     * @return ActionLog
     */
    public static function contactEmail(ObjectManager $om, $client, $loggedInUser, $description)
    {
        $userActionType = UserActionType::CONTACT_EMAIL;
        $log = new ActionLog($client, $loggedInUser, $userActionType, true, $description);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $accountOwner
     * @param Employee $actionBy
     * @param boolean $isSuccessfulLogin
     * @return ActionLog
     */
    public static function loginAdmin(ObjectManager $om, $accountOwner, $actionBy, $isSuccessfulLogin)
    {
        return self::login($om, $accountOwner, $actionBy, $isSuccessfulLogin, UserActionType::ADMIN_LOGIN, false);
    }


    /**
     * @param ObjectManager $om
     * @param Client $accountOwner
     * @param Client|Employee $actionBy
     * @param boolean $isSuccessfulLogin
     * @return ActionLog
     */
    public static function loginUser(ObjectManager $om, $accountOwner, $actionBy, $isSuccessfulLogin)
    {
        return self::login($om, $accountOwner, $actionBy, $isSuccessfulLogin, UserActionType::USER_LOGIN, true);
    }


    /**
     * @param ObjectManager $om
     * @param Person $accountOwner
     * @param Person $actionBy
     * @param boolean $isSuccessfulLogin
     * @param string $userActionType
     * @param boolean $isUserEnvironment
     * @return ActionLog
     */
    private static function login(ObjectManager $om, $accountOwner, $actionBy, $isSuccessfulLogin, $userActionType, $isUserEnvironment)
    {
        $log = new ActionLog($accountOwner, $actionBy, $userActionType, $isSuccessfulLogin, null, $isUserEnvironment);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $admin
     * @return ActionLog
     */
    public static function passwordChangeAdminInProfile(ObjectManager $om, $admin)
    {
        $log = new ActionLog($admin, $admin, UserActionType::ADMIN_PASSWORD_CHANGE, false,null, false);
        DoctrineUtil::persistAndFlush($om, $log);

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
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }

    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param string $emailAddress
     * @return ActionLog
     */
    public static function passwordReset(ObjectManager $om, $client, $emailAddress)
    {
        $userActionType = UserActionType::USER_PASSWORD_RESET;

        $log = new ActionLog($client, $client, $userActionType, false, $emailAddress);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $admin
     * @param string $emailAddress
     * @return ActionLog
     */
    public static function adminPasswordReset(ObjectManager $om, $admin, $emailAddress)
    {
        $userActionType = UserActionType::ADMIN_PASSWORD_RESET;

        $log = new ActionLog($admin, $admin, $userActionType, false, $emailAddress, false);
        DoctrineUtil::persistAndFlush($om, $log);

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
    public static function createMate(ObjectManager $om, $client, $loggedInUser, $location, $content)
    {
        $userActionType = UserActionType::MATE_CREATE;

        $ubn = NullChecker::getUbnFromLocation($location);
        $ulnRam = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content));
        $ulnEwe = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWE, $content));

        $description = 'ubn: '.$ubn.'. Ram uln: '.$ulnRam.'. Ewe uln: '.$ulnEwe;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        DoctrineUtil::persistAndFlush($om, $log);

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
    public static function editMate(ObjectManager $om, $client, $loggedInUser, $location, $content)
    {
        $userActionType = UserActionType::MATE_EDIT;

        $ubn = NullChecker::getUbnFromLocation($location);
        $ulnRam = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content));
        $ulnEwe = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWE, $content));
        $messageId = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MESSAGE_ID, $content);

        $description = 'ubn: '.$ubn.'. Ram uln: '.$ulnRam.'. Ewe uln: '.$ulnEwe.'. messageId: '.$messageId;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }

    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param Ewe $mother
     * @return ActionLog
     */
    public static function createFalseBirth(ObjectManager $om, $client, $loggedInUser, Ewe $mother)
    {
        $userActionType = UserActionType::FALSE_BIRTH_CREATE;

        $description = 'False birth created for Ewe: '. $mother->getUlnCountryCode() . $mother->getUlnNumber();

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }

    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param Ewe $mother
     * @return ActionLog
     */
    public static function createBirth(ObjectManager $om, $client, $loggedInUser, Ewe $mother)
    {
        $userActionType = UserActionType::BIRTH_CREATE;

        $description = 'Litter created for Ewe: '. $mother->getUlnCountryCode() . $mother->getUlnNumber();

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }

    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function createDeclareWeight(ObjectManager $om, $client, $loggedInUser, $content)
    {
        $userActionType = UserActionType::DECLARE_WEIGHT_CREATE;

        $uln = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content));
        $weight = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::WEIGHT, $content);
        $measurementDate = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MEASUREMENT_DATE, $content);

        $description = 'Animal uln: '.$uln.'. weight: '.$weight.'. measurementDate: '.$measurementDate;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function editDeclareWeight(ObjectManager $om, $client, $loggedInUser, $content)
    {
        $userActionType = UserActionType::DECLARE_WEIGHT_EDIT;

        $uln = NullChecker::getUlnOrPedigreeStringFromArray(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content));
        $weight = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::WEIGHT, $content);
        $measurementDate = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MEASUREMENT_DATE, $content);
        $messageId = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MESSAGE_ID, $content);

        $description = 'Animal uln: '.$uln.'. weight: '.$weight.'. measurementDate: '.$measurementDate.'. messageId: '.$messageId;

        $log = new ActionLog($client, $loggedInUser, $userActionType, false, $description);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @return ActionLog
     */
    public static function revokeNsfoDeclaration(ObjectManager $om, $client, $loggedInUser, $messageId)
    {
        $userActionType = UserActionType::NON_IR_REVOKE;
        $log = new ActionLog($client, $loggedInUser, $userActionType, false, 'messageId: '.$messageId);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param string $description
     * @param boolean $isCompleted
     * @return ActionLog
     */
    public static function editAnimalDetails(ObjectManager $om, $client, $loggedInUser, $description, $isCompleted = true)
    {
        $userActionType = UserActionType::ANIMAL_DETAILS_EDIT;
        $log = new ActionLog($client, $loggedInUser, $userActionType, $isCompleted, $description);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Person $loggedInUser
     * @param string $oldGender
     * @param string $newGender
     * @param boolean $isCompleted
     * @return ActionLog
     */
    public static function editGender(ObjectManager $om, $client, $loggedInUser, $oldGender, $newGender, $isCompleted = true)
    {
        $userActionType = UserActionType::GENDER_CHANGE;

        $description = Translation::getGenderInDutch($oldGender) . ' => ' . Translation::getGenderInDutch($newGender);

        $log = new ActionLog($client, $loggedInUser, $userActionType, $isCompleted, $description);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function createAdmin(ObjectManager $om, $actionBy, $content)
    {
        $firstName = $content->get('first_name');
        $lastName = $content->get('last_name');
        $emailAddress = $content->get('email_address');
        $accessLevel = $content->get('access_level');

        $message = $accessLevel.'| '.$emailAddress.' : '.$firstName.' '.$lastName;

        $log = new ActionLog(null, $actionBy, UserActionType::CREATE_ADMIN, false, $message, false);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function editAdmin(ObjectManager $om, $actionBy, $content)
    {
        $personId = $content->get('person_id');

        $message = '';
        $oldFirstName = '';
        $oldLastName = '';
        $oldEmailAddress = '';
        $oldAccessLevel = '';

        $admin = null;
        if ($personId) {
            $admin = $om->getRepository(Employee::class)->findOneBy(['personId' => $personId]);
            if ($admin) {
                $oldFirstName = $admin->getFirstName();
                $oldLastName = $admin->getLastName();
                $oldEmailAddress = $admin->getEmailAddress();
                $oldAccessLevel = $admin->getAccessLevel();
            }
        }

        $changes = [
            //old value       new value
            $oldFirstName => $content->get('first_name'),
            $oldLastName => $content->get('last_name'),
            $oldEmailAddress => $content->get('email_address'),
            $oldAccessLevel => $content->get('access_level'),
        ];

        $anyChanges = false;
        $prefix = '';
        foreach ($changes as $oldValue => $newValue) {
            if ($oldValue !== $newValue) {
                $anyChanges = true;
                $message = $message . $prefix. $oldValue . ' => ' .$newValue;
                $prefix = ', ';
            }
        }

        if ($anyChanges) {
            $log = new ActionLog($admin, $actionBy, UserActionType::EDIT_ADMIN, false, $message, false);
            DoctrineUtil::persistAndFlush($om, $log);
            return $log;
        }

        return null;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param Employee $adminToDeactivate
     * @return ActionLog
     */
    public static function deactivateAdmin(ObjectManager $om, $actionBy, $adminToDeactivate)
    {
        $userActionType = UserActionType::DEACTIVATE_ADMIN;
        if($adminToDeactivate instanceof Employee) {
            $message = $adminToDeactivate->getEmailAddress().' | '.$adminToDeactivate->getFullName();
        } else {
            $message = 'No admin to deactivate found';   
        }

        $log = new ActionLog($adminToDeactivate, $actionBy, $userActionType, false, $message, false);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * Note password change is saved in a separate log
     *
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function editOwnAdminProfile(ObjectManager $om, $actionBy, $content)
    {
        $oldFirstName = $content->get(JsonInputConstant::FIRST_NAME);
        $oldLastName = $content->get(JsonInputConstant::LAST_NAME);
        $oldEmailAddress = $content->get(JsonInputConstant::EMAIL_ADDRESS);

        $message = '';

        $changes = [
            //old value                   new value
            $actionBy->getFirstName() => $content->get(JsonInputConstant::FIRST_NAME),
            $actionBy->getLastName() => $content->get(JsonInputConstant::LAST_NAME),
            $actionBy->getEmailAddress() => $content->get(JsonInputConstant::EMAIL_ADDRESS),
        ];

        $anyChanges = false;
        $prefix = '';
        foreach ($changes as $oldValue => $newValue) {
            if ($oldValue !== $newValue) {
                $anyChanges = true;
                $message = $message . $prefix. $oldValue . ' => ' .$newValue;
                $prefix = ', ';
            }
        }

        if ($anyChanges) {
            $log = new ActionLog($actionBy, $actionBy, UserActionType::EDIT_ADMIN, false, $message, false);
            DoctrineUtil::persistAndFlush($om, $log);
            return $log;
        }

        return null;
    }


    /**
     * @param ObjectManager $om
     * @param ActionLog $log
     * @return ActionLog
     */
    public static function completeActionLog(ObjectManager $om, ActionLog $log)
    {
        if ($log !== null) {
            $log->setIsCompleted(true);
            DoctrineUtil::persistAndFlush($om, $log);
        }

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param ActionLog $log
     * @param Employee $admin
     * @return ActionLog
     */
    public static function completeAdminCreateOrEditActionLog(ObjectManager $om, ActionLog $log, $admin)
    {
        if ($log !== null) {
            $log->setUserAccount($admin);
            $log->setIsCompleted(true);
            DoctrineUtil::persistAndFlush($om, $log);
        }

        return $log;
    }
}