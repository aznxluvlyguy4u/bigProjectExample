<?php

namespace AppBundle\Util;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\TestConstant;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Person;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class Validator
{

    /**
     * @param float $number
     * @param int $maxNumberOfDecimals
     * @return bool
     */
    public static function isNumberOfDecimalsWithinLimit($number, $maxNumberOfDecimals)
    {
        $roundedNumber = round($number,$maxNumberOfDecimals);
        return $roundedNumber == $number;
    }


    /**
     * @param $string
     * @return bool
     */
    public static function isStringAFloat($string)
    {
        if($string == null || $string == '') { return false; }

        //First convert comma decimals to point decimals
        $string = StringUtil::replaceCommasWithDots($string);

        $decimalCount = substr_count($string, '.');
        if($decimalCount > 1) {
            return false;
        }

        $string = str_replace('.', '', $string);
        return ctype_digit($string);
    }
    

    /**
     * validate if Id is of format: AZ123456789012
     *
     * @param string $ulnString
     * @param boolean $includesSpaceBetweenCountryCodeAndNumber
     * @return bool
     */
    public static function verifyUlnFormat($ulnString, $includesSpaceBetweenCountryCodeAndNumber = false)
    {
        if($includesSpaceBetweenCountryCodeAndNumber) {
            $pregMatch = "/([A-Z]{2})+[ ]+([0-9]{8,12})/";
        } else {
            $pregMatch = "/([A-Z]{2})+([0-9]{8,12})/";
        }

        return preg_match($pregMatch,$ulnString);
    }


    public static function verifyUlnNumberFormat($ulnNumber)
    {
        $ulnLength = 12;
        $pregMatch = "/([0-9]{12})/";

        return preg_match($pregMatch,$ulnNumber) && strlen($ulnNumber) == $ulnLength;
    }


    /**
     * @param $animalOrderNumber
     * @return bool
     */
    public static function verifyAnimalOrderNumberFormat($animalOrderNumber)
    {
        if(!ctype_digit($animalOrderNumber) && !is_int($animalOrderNumber)) { return false; }
        $animalOrderNumber = strval($animalOrderNumber);

        $animalOrderNumberLength = 5;
        $pregMatch = "/([0-9]{5})/";

        return preg_match($pregMatch,$animalOrderNumber) && strlen($animalOrderNumber) == $animalOrderNumberLength;
    }


    /**
     * @param string $pedigreeNumber
     * @return bool
     */
    public static function verifyPedigreeNumberFormat($pedigreeNumber)
    {
        $numberLengthIncludingDash = 11;
        return preg_match("/([A-Z0-9]{5}[-][a-zA-Z0-9]{5})/",$pedigreeNumber)
        && strlen($pedigreeNumber) == $numberLengthIncludingDash;
    }


    /**
     * @param string $stn
     * @param boolean $includesSpaceBetweenCountryCodeAndNumber
     * @return bool
     */
    public static function verifyPedigreeCountryCodeAndNumberFormat($stn, $includesSpaceBetweenCountryCodeAndNumber = false)
    {
        if($includesSpaceBetweenCountryCodeAndNumber) {
            $numberLengthIncludingDash = 14;
            $pregMatch = "/([A-Z]{2}[ ][A-Z0-9]{5}[-][a-zA-Z0-9]{5})/";
        } else {
            $numberLengthIncludingDash = 13;
            $pregMatch = "/([A-Z]{2}[A-Z0-9]{5}[-][a-zA-Z0-9]{5})/";
        }
        return preg_match($pregMatch,$stn) && strlen($stn) == $numberLengthIncludingDash;
    }


    /**
     * String must contain at least one of the strings in the array
     *
     * @param string $string
     * @param array $checklist
     * @param $isIgnoreCase boolean
     * @return bool
     */
    public static function isStringContainsAtleastOne($string, $checklist, $isIgnoreCase = false)
    {
        if(count($checklist) == 0) { return false; }

        if($isIgnoreCase) { $string = strtolower($string); }

        $result = false;
        foreach($checklist as $checkString){
            if($isIgnoreCase) { $checkString = strtolower($checkString); }

            $isContainsCheckString = strpos($string, $checkString) !== false;
            if($isContainsCheckString) {
                $result = true;
                break;
            }
        }
        return $result;
    }


    /**
     * String must contain all of the strings given in the array
     *
     * @param string $string
     * @param array $checklist
     * @param $isIgnoreCase boolean
     * @return bool
     */
    public static function isStringContainsAll($string, $checklist, $isIgnoreCase = false)
    {
        if(count($checklist) == 0) { return false; }

        if($isIgnoreCase) { $string = strtolower($string); }

        $result = true;
        foreach($checklist as $checkString){
            if($isIgnoreCase) { $checkString = strtolower($checkString); }

            $isContainsCheckString = strpos($string, $checkString) !== false;
            if(!$isContainsCheckString) {
                $result = false;
                break;
            }
        }
        return $result;
    }


    /**
     * @param array $animalArray
     * @return bool
     */
    public static function verifyUlnFormatOfAnimalInArray($animalArray)
    {
        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);

        if($ulnCountryCode == null || $ulnNumber == null) {
            return false;
        } else {
            return self::verifyUlnFormat($ulnCountryCode.$ulnNumber);
        }
    }


    /**
     * @param Animal $animal
     * @param Client $client
     * @param bool $nullInputResult
     * @return bool
     */
    public static function isAnimalOfClient($animal, $client, $nullInputResult = false)
    {
        //Null check
        if(!($animal instanceof Animal) || !($client instanceof Client)) { return $nullInputResult; }

        $location = $animal->getLocation();
        if($location == null) { return $nullInputResult; }

        $company = $location->getCompany();
        if($company == null) { return $nullInputResult; }

        $ownerOfAnimal = $company->getOwner();
        if($ownerOfAnimal == null) { return $nullInputResult; }

        return $ownerOfAnimal->getId() == $client->getId();
    }


    /**
     * @param Animal $animal
     * @param Location $location
     * @param bool $nullInputResult
     * @return bool
     */
    public static function isAnimalOfLocation($animal, $location, $nullInputResult = false)
    {
        //Null check
        if(!($animal instanceof Animal) || !($location instanceof Location)) { return $nullInputResult; }

        $locationOfAnimal = $animal->getLocation();
        if(!($locationOfAnimal instanceof Location)) { return $nullInputResult; }

        return $locationOfAnimal->getId() == $location->getId();
    }


    /**
     * Note! This will only validate for pedigreeCodes is they exist in the array.
     * If they don't exist in the array or are null, then by default 'true' is returned.
     * 
     * @param ObjectManager $manager
     * @param array $animalArray
     * @param boolean $nullResult
     * @return boolean
     */
    public static function verifyPedigreeCodeInAnimalArray(ObjectManager $manager, $animalArray, $nullResult = true)
    {
        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);

        return self::verifyPedigreeCode($manager, $pedigreeCountryCode, $pedigreeNumber, $nullResult);
    }


    /**
     * Note! This will only validate for pedigreeCodes is they exist in the array.
     * If they don't exist in the array or are null, then by default 'true' is returned.
     * 
     * @param ObjectManager $manager
     * @param string $pedigreeCountryCode
     * @param string $pedigreeNumber
     * @param boolean $nullResult
     * @return bool
     */
    public static function verifyPedigreeCode(ObjectManager $manager, $pedigreeCountryCode, $pedigreeNumber, $nullResult = true)
    {
        if($pedigreeCountryCode != null && $pedigreeNumber != null) {
            /** @var AnimalRepository $animalRepository */
            $animalRepository = $manager->getRepository(Constant::ANIMAL_REPOSITORY);
            $animal = $animalRepository->findByPedigreeCountryCodeAndNumber($pedigreeCountryCode, $pedigreeNumber);
            return $animal != null;
        }
        return $nullResult;
    }


    /**
     * @param Animal $animal
     * @return bool
     */
    public static function isAnimalMale(Animal $animal)
    {
        if($animal instanceof Ram) {
            return true;
        } elseif ($animal instanceof Neuter) {
            $genderValue = $animal->getGender();
            return $genderValue === GenderType::MALE || $genderValue === GenderType::M;
        }

        return false;
    }


    /**
     * @param array $animalArray
     * @return bool
     */
    public static function validateNonNsfoAnimalUlnAndPedigree(ObjectManager $manager, array $animalArray)
    {
        //First validate if uln or pedigree exists
        $containsUlnOrPedigree = NullChecker::arrayContainsUlnOrPedigree($animalArray);
        if(!$containsUlnOrPedigree) {
            return false;
        }
        
        //Then validate the uln if it exists
        $ulnString = NullChecker::getUlnStringFromArray($animalArray, null);
        if ($ulnString != null) {
            return Validator::verifyUlnFormat($ulnString);
        }

        //Validate pedigree if it exists
        return self::verifyPedigreeCodeInAnimalArray($manager, $animalArray, false);
    }


    /**
     * @param Person $user
     * @param null $ghostToken
     * @return bool
     */
    public static function isUserLoginWithActiveCompany($user, $ghostToken = null)
    {
        if($user instanceof Client) {
            return self::isCompanyActiveOfClient($user);
        } else if($user instanceof Employee && $ghostToken instanceof Token) {
            return self::isCompanyActiveOfGhostToken($ghostToken);
        } else {
            //only Clients and Employees with GhostTokens are able to login
            return false;
        }
    }


    /**
     * TODO At the moment any Client(user) can only own one Company OR be an employee a one company. When this changes, this validation check has to be updated.
     * @param Client $client
     * @return bool
     */
    private static function isCompanyActiveOfClient($client)
    {
        //null check
        if(!($client instanceof Client)) { return false; }

        if($client->hasEmployer()) {

            //is user employee at the company
            $isActive = $client->getEmployer()->isActive();
            if($isActive) {
                return true;
            } else {
                return false;
            }

        } else {
            //is owner at at least one of owner's companies
            $companies = $client->getCompanies();
            $deactivatedCompanies = 0;
            /** @var Company $company */
            foreach ($companies as $company) {
                if(!$company->isActive()) {
                    $deactivatedCompanies++;
                }
            }

            if($deactivatedCompanies == $companies->count()) {
                //has no active companies
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * @param Token $ghostToken
     * @return bool
     */
    private static function isCompanyActiveOfGhostToken($ghostToken)
    {
        //null check
        if(!($ghostToken instanceof Token)) { return false; }

        $tokenOwner = $ghostToken->getOwner();
        if($tokenOwner instanceof Client) {
            return self::isCompanyActiveOfClient($tokenOwner);
        } else {
            //not a client, so cannot even have any companies
            return false;
        }

    }


    /**
     * @param string $message
     * @param int $code The HTTP code
     * @param array $errors
     * @return JsonResponse
     */
    public static function createJsonResponse($message, $code, $errors = array())
    {
        //TODO replace use of this function with ResultUtil::errorResult()
        return ResultUtil::errorResult($message, $code, $errors);
    }


    /**
     * @param ObjectManager $manager
     * @param Client $client
     * @param $messageId
     * @return DeclareNsfoBase|Mate|DeclareWeight|boolean
     */
    public static function isNonRevokedNsfoDeclarationOfClient(ObjectManager $manager, Client $client, $messageId)
    {
        /** @var DeclareNsfoBase $declaration */
        $declaration = $manager->getRepository(DeclareNsfoBase::class)->findOneByMessageId($messageId);

        //null check
        if(!($declaration instanceof DeclareNsfoBase) || $messageId == null) { return false; }

        //Revoke check, to prevent data loss by incorrect data
        if($declaration->getRequestState() == RequestStateType::REVOKED) { return false; }

        /** @var Location $location */
        $location = $manager->getRepository(Location::class)->findOneByUbn($declaration->getUbn());

        $owner = NullChecker::getOwnerOfLocation($location);

        if($owner instanceof Client && $client instanceof Client) {
            /** @var Client $owner */
            if($owner->getId() == $client->getId()) {
                return $declaration;
            }
        }

        return false;
    }


    /**
     * Test if database used is the test database.
     * 
     * @param EntityManagerInterface|ObjectManager $em
     * @throws \Exception
     */
    public static function isTestDatabase(ObjectManager $em)
    {
        /** @var Connection $connection */
        $connection = $em->getConnection();
        $databaseName = $connection->getDatabase();
        $host = $connection->getHost();
        
        $isIgnoreCase = true;
        //$isLocalHost = self::isStringContainsAtleastOne($host, ['localhost', '127.0.0.1'], $isIgnoreCase);
        $isTestDatabaseName = self::isStringContainsAtleastOne($databaseName, ['test'], $isIgnoreCase);
        $isNotProductionDatabaseName = !self::isStringContainsAtleastOne($databaseName, ['prod'], $isIgnoreCase);

        if (!($isTestDatabaseName && $isNotProductionDatabaseName)) {
            throw new \Exception(TestConstant::TEST_DB_ERROR_MESSAGE, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @param array $array1
     * @param array $array2
     * @return bool
     */
    public static function areArrayContentsUnique($array1, $array2)
    {
        if(empty($array1) && empty($array2)) {
            return false;

        } elseif(empty($array1) || empty($array2)) {
            return true;
            
        } else {
            $isUnique = true;
            foreach ($array1 as $item) {
                if(in_array($item, $array2))
                {
                    $isUnique = false;
                    break;
                }
            }
            return $isUnique;
        }
    }


    /**
     * @param string|int $ubn
     * @return bool
     */
    public static function hasValidUbnFormat($ubn)
    {
        //Verify type, ensure ubn is a string
        if(is_int($ubn)) { $ubn = (string)$ubn; }
        else if(is_string($ubn)) {  if(!ctype_digit($ubn)) { return false; }}
        else { return false; }

        $maxLength = 7;
        $minLength = 2;
        $length = strlen($ubn);

        if($length < $minLength || $length > $maxLength) { return false; }

        $ubnReversed = strrev(str_pad($ubn, $maxLength, 0, STR_PAD_LEFT));
        $ubnDigits = str_split($ubnReversed, 1);
        $weights = [1, 3, 7, 1, 3, 7, 1];

        $sum = 0;
        for($i=0; $i < $maxLength; $i++) {
            $sum += intval($ubnDigits[$i]) * $weights[$i];
        }

        return $sum%10 == 0;
    }


    /**
     * Returns true is the animals should be included in the historicLivestock for the given location.
     *
     * @param ObjectManager $em
     * @param Animal $animal
     * @param Location $locationOfUser
     * @return bool
     */
    public static function isAnimalPublicForLocation(ObjectManager $em, Animal $animal, Location $locationOfUser)
    {
        if(!($animal instanceof Animal) || !($locationOfUser instanceof Location)) { return false; }

        //1. Always show animals on own location/ubn

        if($animal->getLocation()) {
            if($animal->getLocation()->getId() == $locationOfUser->getId()) { return true; }
        }

        $locationOfBirth = $animal->getLocationOfBirth();
        if($locationOfBirth) {

            //2. Always allow breeder to see his own animals!
            if($locationOfUser->getId() == $locationOfBirth->getId()) {
                return true;
            }

            $company = $locationOfBirth->getCompany();
            if($company) {

                //3. Always allow, if location was deactivated
                if(!$company->isActive()){
                    return true;

                    //4. Else only show Animal if it is an historic animals and if owner ubnOfBirth allows it
                } else {
                    return $company->getIsRevealHistoricAnimals();
                }
            }
        }

        //5. If no locationOfBirth is registered, show if animal has animal has ever been on the location of the user.

        /** @var \Doctrine\ORM\EntityManager $em */
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
            ->select('COUNT(animalResidence.id)')
            ->from ('AppBundle:AnimalResidence', 'animalResidence')
            ->where('animalResidence.location = :locationId')
            ->andWhere('animalResidence.animal = :animalId')
            ->setParameter('locationId', $locationOfUser->getId())
            ->setParameter('animalId', $animal->getId());

        $query = $queryBuilder->getQuery();
        //TODO use redis, for example: $query->useResultCache(true, 3600, 'animalPublicForLocation');
        $count = $query->getResult()[0][1];

        if($count > 0) { return true; }

        return false;
    }


    /**
     * @param string $value
     * @return bool
     */
    public static function isStringFloatFormat($value)
    {
        return strval(floatval($value)) === $value;
    }


    /**
     * @param string $emailAddress
     * @return boolean
     */
    public static function isEmailAddressFormat($emailAddress)
    {
        return filter_var($emailAddress, FILTER_VALIDATE_EMAIL);
    }


    /**
     * @param string $emailAddress
     * @param bool $throwException
     * @param int $errorCode
     * @return JsonResponse|bool
     * @throws \Exception
     */
    public static function validateEmailAddress($emailAddress, $throwException = false, $errorCode = 428)
    {
        $isValid = self::isEmailAddressFormat($emailAddress);

        if ($isValid) {
            return true;
        }

        $errorMessage = 'Invalid email address: ' . $emailAddress;

        if ($throwException) {
            throw new \Exception($errorMessage, $errorCode);
        }

        return ResultUtil::errorResult($errorMessage, $errorCode);
    }

}