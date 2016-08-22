<?php

namespace AppBundle\Validation;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class Validator
{
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
    
}