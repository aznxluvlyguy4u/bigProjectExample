<?php

namespace AppBundle\Util;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
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
use Doctrine\Common\Persistence\ObjectManager;

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
        if($roundedNumber == $number) {
            return true;
        } else {
            return false;
        }
    }
    

    /**
     * validate if Id is of format: AZ123456789
     *
     * @param string $ulnString
     * @return bool
     */
    public static function verifyUlnFormat($ulnString)
    {
        $countryCodeLength = 2;
        $numberLength = 12;
        $ulnLength = $countryCodeLength + $numberLength;

        if(preg_match("/([A-Z]{2})+([0-9]{12})/",$ulnString)
            && strlen($ulnString) == $ulnLength) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param string $pedigreeNumber
     * @return bool
     */
    public static function verifyPedigreeNumberFormat($pedigreeNumber)
    {
        $numberLengthIncludingDash = 11;

        if(preg_match("/([A-Z0-9]{5}[-][A-Z0-9]{5})/",$pedigreeNumber)
            && strlen($pedigreeNumber) == $numberLengthIncludingDash) {
            return true;
        } else {
            return false;
        }
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

        if($ownerOfAnimal->getId() == $client->getId()) {
            return true;
        } else {
            return false;
        }
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

        if($locationOfAnimal->getId() == $location->getId()) {
            return true;
        } else {
            return false;
        }
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

            if($animal != null) {
                return true;
            } else {
                return false;
            }

        } else {
            return $nullResult;
        }
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
            if ($genderValue === GenderType::MALE || $genderValue === GenderType::M) {
                return true;
            }
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
        //Success message
        if($errors == null || sizeof($errors) == 0){
            $result = array(
                Constant::MESSAGE_NAMESPACE => $message,
                Constant::CODE_NAMESPACE => $code);

        //Error message
        } else {
            $result = array();
            foreach ($errors as $errorMessage) {
                $errorArray = [
                    Constant::CODE_NAMESPACE => $code,
                    Constant::MESSAGE_NAMESPACE => $errorMessage
                ];
                $result[] = $errorArray;
            }
        }

        return new JsonResponse([JsonInputConstant::RESULT => $result], $code);
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
}