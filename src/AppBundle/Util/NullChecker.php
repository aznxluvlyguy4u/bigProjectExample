<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use Symfony\Component\Filesystem\Filesystem;

class NullChecker
{
    const DEFAULT_FLOAT_ACCURACY = 0.0001;

    /**
     * @param $input
     * @return bool
     */
    public static function isNull($input)
    {
        return !self::isNotNull($input);
    }
    
    /**
     * @param $input
     * @return bool
     */
    public static function isNotNull($input)
    {
        if($input != null && $input != '' && $input != ' ') {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $input
     * @return bool
     */
    public static function numberIsNull($input)
    {
        return !self::numberIsNotNull($input);
    }

    /**
     * @param $input
     * @return bool
     */
    public static function numberIsNotNull($input)
    {
        if($input != null && $input != 0 && $input !== '' && $input !== ' ') {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param float $float
     * @param float $accuracy
     * @return bool
     */
    public static function floatIsNotZero($float, $accuracy = self::DEFAULT_FLOAT_ACCURACY)
    {
        return !NumberUtil::isFloatZero($float, $accuracy);
    }



    /**
     * @param $array
     * @return int
     */
    public static function getArrayCount($array)
    {
        if($array != null) {
            return sizeof($array);
        } else {
            return 0;
        }
    }

    /**
     * @param array $animalArray
     * @return bool
     */
    public static function arrayContainsUlnOrPedigree($animalArray)
    {
        if($animalArray == null) { return false; }

        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);
        if ($ulnCountryCode != null && $ulnNumber != null) {
            return true;
        }

        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        if ($pedigreeCountryCode != null && $pedigreeNumber != null) {
            return true;
        }

        //else
        return false;
    }
    
    /**
     * @param array $array
     * @param string $replacementText
     * @return string
     */
    public static function getUlnOrPedigreeStringFromArray($array, $replacementText = "-")
    {
        if($array == null) {return $replacementText; }
        
        $uln = self::getUlnStringFromArray($array, $replacementText);
        if($uln != $replacementText) {
            return $uln;
        } else {
            return self::getPedigreeStringFromArray($array, $replacementText);
        }
    }


    /**
     * @param array $array
     * @param string $replacementText
     * @return string
     */
    public static function getUlnStringFromArray($array, $replacementText = "-")
    {
        if($array == null) {return $replacementText; }
        
        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $array);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $array);

        if($ulnCountryCode != null && $ulnCountryCode != null) {
            return $ulnCountryCode.$ulnNumber;
        } else {
            return $replacementText;
        }
    }


    /**
     * @param array $array
     * @param string $replacementText
     * @return string
     */
    public static function getPedigreeStringFromArray($array, $replacementText = "-")
    {
        if($array == null) {return $replacementText; }
        
        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $array);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $array);

        if($pedigreeCountryCode != null && $pedigreeCountryCode != null) {
            return $pedigreeCountryCode.$pedigreeNumber;
        } else {
            return $replacementText;
        }
    }


    /**
     * @param Location $location
     * @param string $replacementText
     * @return string
     */
    public static function getUbnFromLocation($location, $replacementText = "-")
    {
        if($location instanceof Location) {
            return $location->getUbn();
        } else {
            return $replacementText;
        }
    }
    
    
    /**
     * @param Location $location
     * @param mixed $nullResultReplacement
     * @return Client|mixed
     */
    public static function getOwnerOfLocation($location, $nullResultReplacement = null)
    {
        if(!($location instanceof Location)) { return $nullResultReplacement; }

        /** @var Location $location */
        $company = $location->getCompany();
        if(!($company instanceof Company)) { return $nullResultReplacement; }

        $owner = $company->getOwner();
        if(!($owner instanceof Client)) {
            return $nullResultReplacement;
        } else {
            return $owner;
        }
    }


    /**
     * @param DeclareNsfoBase $declareNsfo
     * @return string
     */
    public static function getRevokerPersonId($declareNsfo, $nullReplacementText = null)
    {
        if(!($declareNsfo instanceof DeclareNsfoBase)) { return $nullReplacementText; }

        $revoker = $declareNsfo->getRevokedBy();
        if($revoker instanceof Person) {
            return Utils::fillNullOrEmptyString($revoker->getPersonId(), $nullReplacementText);
        } else {
            return $nullReplacementText;
        }
    }


    /**
     * @param string $folderPath
     */
    public static function createFolderPathIfNull($folderPath)
    {
        $fs = new Filesystem();
        if(!$fs->exists($folderPath)) {
            $fs->mkdir($folderPath);
        }
    }


    /**
     * @param Animal $animal
     * @param string $nullReplacementText
     * @return string|null
     */
    public static function getNullCheckedDateOfBirthAsString($animal, $nullReplacementText = null)
    {
        $dateOfBirth = self::getNullCheckedDateOfBirth($animal, $nullReplacementText);
        if($dateOfBirth != $nullReplacementText && $dateOfBirth != null) {
            return $dateOfBirth->format('d-m-Y');
        } else {
            return $nullReplacementText;
        }
    }


    /**
     * @param Animal $animal
     * @param string $nullReplacementText
     * @return \DateTime|null
     */
    public static function getNullCheckedDateOfBirth($animal, $nullReplacementText = null)
    {
        if($animal instanceof Animal) {
            $dateOfBirth = $animal->getDateOfBirth();
            if($dateOfBirth != null) {
                return Utils::fillNullOrEmptyString($animal->getDateOfBirth(), $nullReplacementText);
            }
        }
        return $nullReplacementText;
    }


    /**
     * @param array $array
     * @param string $replacementString
     * @return array
     */
    public static function replaceNullInNestedArray($array, $replacementString = "")
    {
        $count = count($array);
        $nestedKeys = [];
        if($count > 0) { $nestedKeys = array_keys($array[0]); }

        $keys = array_keys($array);

        foreach ($keys as $key) {
            foreach ($nestedKeys as $nestedKey) {
                if($array[$key][$nestedKey] == null) { $array[$key][$nestedKey] = ""; }
            }
        }
        
        return $array;
    }
}