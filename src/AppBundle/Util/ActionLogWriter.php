<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Location;
use AppBundle\Entity\Message;
use AppBundle\Entity\Person;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\UserActionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

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
        $log->setIsRvoMessage(true);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param DeclareArrival $arrival
     * @param Client $arrivalOwner
     * @param bool $isCompleted
     * @return ActionLog
     */
    public static function declareArrival(DeclareArrival $arrival, Client $arrivalOwner, $isCompleted = true)
    {
        $origin = 'ubn previous owner: '.$arrival->getUbnPreviousOwner();
        $uln = $arrival->getUlnCountryCode().$arrival->getUlnNumber();
        $description = 'ubn destination: '.$arrival->getUbn().'. '.$origin.'. uln: '.$uln;

        $log = new ActionLog($arrivalOwner, $arrival->getActionBy(), UserActionType::DECLARE_ARRIVAL, $isCompleted, $description);
        $log->setIsRvoMessage(true);
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
        $log->setIsRvoMessage(true);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param DeclareDepart $declareDepart
     * @param Client $departOwner
     * @param bool $isCompleted
     * @return ActionLog
     */
    public static function declareDepart(DeclareDepart $declareDepart, Client $departOwner, $isCompleted = true)
    {
        $destination = 'ubn new owner: '.$declareDepart->getUbnNewOwner();
        $uln = $declareDepart->getUlnCountryCode().$declareDepart->getUlnNumber();

        $description = 'ubn: '.$declareDepart->getUbn().'. '.$destination.'. uln: '.$uln;

        $log = new ActionLog($departOwner, $declareDepart->getActionBy(), UserActionType::DECLARE_DEPART, $isCompleted, $description);
        $log->setIsRvoMessage(true);
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
        $log->setIsRvoMessage(true);
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
        $log->setIsRvoMessage(true);
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
        $log->setIsRvoMessage(true);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $em
     * @param array $requestMessages
     * @param Client $client
     * @return array
     * @throws \Exception
     */
    public static function createBirth(ObjectManager $em, $requestMessages, Client $client = null)
    {
        $logs = [];

        if (count($requestMessages) === 0) { return $logs; }

        /** @var DeclareBirth $requestMessage */
        foreach ($requestMessages as $requestMessage) {

            $dateOfBirth = $requestMessage->getDateOfBirth()->format('Y-m-d');
            $gender = Translation::getGenderInDutch($requestMessage->getGender());
            $uln = $requestMessage->getUlnCountryCode().$requestMessage->getUlnNumber();
            $ulnMother = $requestMessage->getUlnCountryCodeMother().$requestMessage->getUlnMother();
            $ulnFather = $requestMessage->getUlnCountryCodeFather().$requestMessage->getUlnFather();

            $litterData = '';
            $litter = $requestMessage->getLitter();
            if ($litter) {
                $litterData = ', Worp: nLing '. $litter->getSize() .' (levend ' .$litter->getBornAliveCount() . ', dood ' . $litter->getStillbornCount().')';
            }

            $description = $gender.' '.$uln.' GebDatum '.$dateOfBirth.', moeder: '.$ulnMother. ', vader: '.$ulnFather.$litterData;

            $clientOfDeclare = $client;
            if ($client === null) {
                if ($requestMessage->getLocation()) {
                    $clientOfDeclare = $requestMessage->getLocation()->getOwner();
                }
            }

            $log = new ActionLog($clientOfDeclare, $requestMessage->getActionBy(), UserActionType::DECLARE_BIRTH, false, $description);
            $log->setIsRvoMessage(true);
            $em->persist($log);
            $logs[] = $log;
        }
        $em->flush();

        return $logs;
    }


    /**
     * @param ObjectManager $em
     * @param Litter $litter
     * @param Person $actionBy
     * @param Client $client
     * @return ActionLog
     */
    public static function revokeLitter(ObjectManager $em, Litter $litter, Person $actionBy, Client $client)
    {
        $dateOfBirth = $litter->getLitterDate()->format('Y-m-d');

        $description = 'Intrekking Worp: WorpDatum '.$dateOfBirth;

        if ($litter->getAnimalMother()) {
            $description = $description .', moeder: '.$litter->getAnimalMother()->getUln();
        }

        if ($litter->getAnimalFather()) {
            $description = $description .', vader: '.$litter->getAnimalFather()->getUln();
        }

        $description = $description . ', Worp: nLing '. $litter->getSize() .' (levend ' .$litter->getBornAliveCount() . ', dood ' . $litter->getStillbornCount().')';


        $log = new ActionLog($client, $actionBy, UserActionType::BIRTH_REVOKE, true, $description);
        $log->setIsRvoMessage(false);
        if ($litter->getBornAliveCount() > 0) {
            $log->setIsRvoMessage(true);
        }

        DoctrineUtil::persistAndFlush($em, $log);

        return  $log;
    }


    /**
     * @param ObjectManager $om
     * @param ArrayCollection $content
     * @param Company $company
     * @param Person $loggedInUser
     * @return ActionLog
     */
    public static function createCompany(ObjectManager $om, ArrayCollection $content, Company $company, $loggedInUser)
    {
        try {
            $description = self::getCompanyDescription($company).', user input : '.ArrayUtil::implode($content);
            $log = new ActionLog($company->getOwner(), $loggedInUser, UserActionType::CREATE_COMPANY, true, $description, false);
            DoctrineUtil::persistAndFlush($om, $log);
        } catch (\Exception $exception) {
            $description = $company->getCompanyName();
            $log = new ActionLog($company->getOwner(), $loggedInUser, UserActionType::CREATE_COMPANY, true, $description, false);
            DoctrineUtil::persistAndFlush($om, $log);
        }

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param ArrayCollection $content
     * @param Company $company
     * @param Person $loggedInUser
     * @return ActionLog
     */
    public static function editCompany(ObjectManager $om, ArrayCollection $content, Company $company, $loggedInUser)
    {
        try {
            $description = self::getCompanyDescription($company).', user input : '.ArrayUtil::implode($content);
            $log = new ActionLog($company->getOwner(), $loggedInUser, UserActionType::EDIT_COMPANY, true, $description, false);
            DoctrineUtil::persistAndFlush($om, $log);
        } catch (\Exception $exception) {
            $description = $company->getCompanyName();
            $log = new ActionLog($company->getOwner(), $loggedInUser, UserActionType::EDIT_COMPANY, true, $description, false);
            DoctrineUtil::persistAndFlush($om, $log);
        }

        return $log;
    }


    /**
     * @param Company $company
     * @return string
     */
    private static function getCompanyDescription(Company $company)
    {
        $description = '';
        $prefix = '';

        if ($company) {
            if ($company->getCompanyName() !== null && trim($company->getCompanyName()) !== '' ) {
                $companyName = $company->getCompanyName();
            } else {
                $companyName = $company->getCompanyId();
            }

            $description = $description . $prefix . 'Bedrijf: '. $companyName;
            $prefix = ', ';

            if ($company->getOwner()) {
                $description = $description . $prefix . 'eigenaar: '. $company->getOwner()->getFullName();
                $prefix = ', ';
            }

            if (count($company->getLocations()) > 0) {
                $description = $description . $prefix . 'ubns: ';
                $prefix = ', ';

                $ubnPrefix = '';
                /** @var Location $location */
                foreach ($company->getLocations() as $location)
                {
                    if ($location->getIsActive()) {
                        $description = $description . $ubnPrefix . $location->getUbn();
                        $ubnPrefix = ', ';
                    }
                }
            }
        }

        return $description;
    }


    /**
     * @param ObjectManager $om
     * @param Person $loggedInUser
     * @param bool $isActive
     * @param Company $company
     * @return ActionLog
     */
    public static function activeStatusCompany(ObjectManager $om, $isActive, Company $company, $loggedInUser)
    {
        $userActionType = $isActive ? UserActionType::ACTIVATE_COMPANY : UserActionType::DEACTIVATE_COMPANY;

        try {
            $description = self::getCompanyDescription($company);
            $log = new ActionLog($company->getOwner(), $loggedInUser, $userActionType, true, $description, false);
            DoctrineUtil::persistAndFlush($om, $log);
        } catch (\Exception $exception) {
            $description = $company->getCompanyName();
            $log = new ActionLog($company->getOwner(), $loggedInUser, $userActionType, true, $description, false);
            DoctrineUtil::persistAndFlush($om, $log);
        }

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
        $log->setIsRvoMessage(true);
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
     * @param boolean $isCompleted
     * @return ActionLog
     */
    public static function contactEmail(ObjectManager $om, $client, $loggedInUser, $description, $isCompleted = true)
    {
        $userActionType = UserActionType::CONTACT_EMAIL;
        $log = new ActionLog($client, $loggedInUser, $userActionType, $isCompleted, $description);
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
     * @param VwaEmployee $vwaEmployee
     * @param boolean $isSuccessfulLogin
     * @return ActionLog
     */
    public static function loginVwaEmployee(ObjectManager $om, $vwaEmployee, $isSuccessfulLogin)
    {
        $actionBy = $isSuccessfulLogin ? $vwaEmployee : null;
        return self::login($om, $vwaEmployee, $actionBy, $isSuccessfulLogin, UserActionType::VWA_LOGIN, false, true);
    }


    /**
     * @param ObjectManager $om
     * @param Person $accountOwner
     * @param Person $actionBy
     * @param boolean $isSuccessfulLogin
     * @param string $userActionType
     * @param boolean $isUserEnvironment
     * @param boolean $isVwaEnvironment
     * @return ActionLog
     */
    private static function login(ObjectManager $om, $accountOwner, $actionBy, $isSuccessfulLogin, $userActionType,
                                  $isUserEnvironment, $isVwaEnvironment = false)
    {
        $log = new ActionLog($accountOwner, $actionBy, $userActionType, $isSuccessfulLogin, null, $isUserEnvironment, $isVwaEnvironment);
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
    public static function clientPasswordReset(ObjectManager $om, $client, $emailAddress)
    {
        $log = new ActionLog($client, $client, UserActionType::USER_PASSWORD_RESET, false,
            $emailAddress);
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
        $log = new ActionLog($admin, $admin, UserActionType::ADMIN_PASSWORD_RESET, false,
            $emailAddress, false);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Person $person
     * @param string $emailAddress
     * @param string $userActionType
     * @return ActionLog
     */
    public static function passwordResetRequest(ObjectManager $om, $person, $userActionType, $emailAddress)
    {
        switch ($userActionType) {
            case UserActionType::USER_PASSWORD_RESET:
                $isUserEnvironment = true;
                $isVwaEnvironment = false;
                break;
            case UserActionType::ADMIN_PASSWORD_RESET:
                $isUserEnvironment = false;
                $isVwaEnvironment = false;
                break;
            case UserActionType::VWA_PASSWORD_RESET:
                $isUserEnvironment = false;
                $isVwaEnvironment = true;
                break;
            default:
                $isUserEnvironment = true;
                $isVwaEnvironment = false;
                break;
        }

        $log = new ActionLog($person, $person, $userActionType, false,
            self::getPasswordResetDescription($emailAddress, true), $isUserEnvironment, $isVwaEnvironment);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Person $person
     * @return ActionLog
     */
    public static function passwordResetConfirmation(ObjectManager $om, $person)
    {
        $userActionType = UserActionType::USER_PASSWORD_RESET;
        $isUserEnvironment = true;
        $isVwaEnvironment = false;
        if ($person instanceof Employee) {
            $userActionType = UserActionType::ADMIN_PASSWORD_RESET;
            $isUserEnvironment = false;
            $isVwaEnvironment = false;
        } elseif ($person instanceof VwaEmployee) {
            $userActionType = UserActionType::VWA_PASSWORD_RESET;
            $isUserEnvironment = false;
            $isVwaEnvironment = true;
        }

        $log = new ActionLog($person, $person, $userActionType, true,
            self::getPasswordResetDescription($person->getEmailAddress(), false),
            $isUserEnvironment, $isVwaEnvironment);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param string $emailAddress
     * @param boolean $isResetRequest
     * @return string
     */
    private static function getPasswordResetDescription($emailAddress, $isResetRequest)
    {
        $descriptionType = $isResetRequest ? 'aanvraag' : 'bevestiging';
        return $emailAddress . ': wachtwoord reset '.$descriptionType;
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
     * @param Person $accountOwner
     * @param Person $actionBy
     * @param Message $message
     * @return ActionLog
     */
    public static function changeMessageReadStatus(ObjectManager $om, $accountOwner, $actionBy, $message)
    {
        $read = $message->isRead() ? 'READ': 'UNREAD';
        $description = $read . ' ' . $message->getType().' '.$message->getData();

        $log = new ActionLog($accountOwner, $actionBy, UserActionType::CHANGE_READ_MESSAGE_STATUS, true, $description, true);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Person $accountOwner
     * @param Person $actionBy
     * @param Message $message
     * @return ActionLog
     */
    public static function changeMessageHideStatus(ObjectManager $om, $accountOwner, $actionBy, $message)
    {
        $hide = $message->isHidden() ? 'HIDE': 'UNHIDE';
        $description = $hide . ' ' . $message->getType().' '.$message->getData();

        $log = new ActionLog($accountOwner, $actionBy, UserActionType::CHANGE_HIDE_MESSAGE_STATUS, true, $description, true);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param EntityManagerInterface $em
     * @param Person $actionBy
     * @param VwaEmployee $vwaEmployee
     * @param boolean $isReactivation
     * @return ActionLog
     */
    public static function createVwaEmployee(EntityManagerInterface $em, Person $actionBy,
                                             VwaEmployee $vwaEmployee, $isReactivation)
    {
        $userActionType = $isReactivation ? UserActionType::VWA_EMPLOYEE_REACTIVATE : UserActionType::VWA_EMPLOYEE_CREATE;

        $description = $vwaEmployee->getFullName() .',  '. $vwaEmployee->getEmailAddress() . ', invited by: '
            .$actionBy->getFullName();

        $log = new ActionLog($vwaEmployee, $actionBy, $userActionType,true,$description,false,true);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param EntityManagerInterface $em
     * @param Person $actionBy
     * @param VwaEmployee $vwaEmployee
     * @param string $description
     * @return ActionLog
     */
    public static function editVwaEmployee(EntityManagerInterface $em, Person $actionBy, VwaEmployee $vwaEmployee, $description)
    {
        $log = new ActionLog($vwaEmployee, $actionBy, UserActionType::VWA_EMPLOYEE_EDIT,true,$description,false,true);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param EntityManagerInterface $em
     * @param Person $actionBy
     * @param VwaEmployee $vwaEmployee
     * @return ActionLog
     */
    public static function deleteVwaEmployee(EntityManagerInterface $em, Person $actionBy, VwaEmployee $vwaEmployee)
    {
        $log = new ActionLog($vwaEmployee, $actionBy, UserActionType::VWA_EMPLOYEE_DEACTIVATE,true,null,false,true);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param ActionLog|array $log
     * @return ActionLog|array
     */
    public static function completeActionLog(ObjectManager $om, $log)
    {
        if (is_array($log)) {
            foreach ($log as $item) {
                self::completeSingleActionLog($om, $item, false);
            }
            $om->flush();
            return $log;
        }

        return self::completeSingleActionLog($om, $log);
    }


    /**
     * @param ObjectManager $om
     * @param ActionLog $log
     * @param bool $flush
     * @return ActionLog
     */
    private static function completeSingleActionLog(ObjectManager $om, ActionLog $log, $flush = true)
    {
        if ($log !== null) {
            $log->setIsCompleted(true);
            $om->persist($log);

            if($flush) { $om->flush(); }
        }

        return $log;
    }


    /**
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public static function initializeIsRvoMessageValues(Connection $conn, $cmdUtil = null)
    {
        if ($cmdUtil) { $cmdUtil->writeln('Initializing is_rvo_message boolean in action_log table ...'); }

        $actionTypeFilter = '';
        $prefix = '';
        foreach (UserActionType::getRvoMessageActionTypes() as $requestType)
        {
            $actionTypeFilter = $actionTypeFilter . $prefix . "'".$requestType."'";
            $prefix = ',';
        }

        $sql = "UPDATE action_log SET is_rvo_message = TRUE
                WHERE user_action_type IN ($actionTypeFilter)
                AND is_rvo_message = FALSE";
        $updateCount = SqlUtil::updateWithCount($conn, $sql);

        $countString = $updateCount === 0 ? 'No': $updateCount;

        if ($cmdUtil) { $cmdUtil->writeln($countString . ' records updated'); }
        return $updateCount;
    }

}