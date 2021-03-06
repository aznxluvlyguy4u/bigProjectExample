<?php

namespace AppBundle\Component;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthQueue;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class Utils
 *
 * Simple utility functions that don't need any service injection
 *
 * @package AppBundle\Component
 */
class Utils
{
    /**
     * @return string
     */
    static function generateTokenCode()
    {
        return sha1(uniqid(rand(), true));
    }

    /**
     * @return string
     */
    static function generatePersonId()
    {
        return sha1(uniqid(rand(), true));
    }

    /**
     * @param object $object
     * @return string
     */
    static function getClassName($object) {
        $classNameWithPath = get_class($object);
        $pathArray = explode('\\', $classNameWithPath);
        $className = $pathArray[sizeof($pathArray)-1];

        return $className;
    }

    /**
     * @param object $object
     * @return string
     */
    static function getRepositoryNameSpace($object) {
        $classNameWithPath = get_class($object);
        $pathArray = explode('\\', $classNameWithPath);
        $n = sizeof($pathArray);
        $repositoryNameSpace = $pathArray[$n-3] . ":" . $pathArray[$n-1];

        return $repositoryNameSpace;
    }


    /**
     * @param string $ulnString
     * @param bool $hasSpaceBetweenCountryCodeAndNumber
     * @return array|null
     */
    public static function getUlnFromString($ulnString, $hasSpaceBetweenCountryCodeAndNumber = false)
    {
        //Verify format first
        if(!Validator::verifyUlnFormat($ulnString, $hasSpaceBetweenCountryCodeAndNumber)) {
            return null;
        }

        return self::separateFirstToCharsAndEndOfString(
            $ulnString,
            $hasSpaceBetweenCountryCodeAndNumber,
            Constant::ULN_COUNTRY_CODE_NAMESPACE,
            Constant::ULN_NUMBER_NAMESPACE
        );
    }


    /**
     * @param string $stnString
     * @param bool $hasSpaceBetweenCountryCodeAndNumber
     * @return array|null
     */
    public static function getStnFromString($stnString, $hasSpaceBetweenCountryCodeAndNumber = false)
    {
        //Verify format first
        if(!Validator::verifyPedigreeCountryCodeAndNumberFormat($stnString, $hasSpaceBetweenCountryCodeAndNumber)) {
            return null;
        }

        return self::separateFirstToCharsAndEndOfString(
            $stnString,
            $hasSpaceBetweenCountryCodeAndNumber,
            Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE,
            Constant::PEDIGREE_NUMBER_NAMESPACE
        );
    }


    /**
     * @param $string
     * @param $isSeparatedBySpace
     * @param int|string $key1
     * @param int|string $key2
     * @return array
     */
    protected static function separateFirstToCharsAndEndOfString($string, $isSeparatedBySpace, $key1 = 0, $key2 = 1)
    {
        if($isSeparatedBySpace) {
            $parts = explode(' ', $string);
            $firstTwoChars = $parts[0];
            $endOfString = $parts[1];
        } else {
            $firstTwoChars = mb_substr($string, 0, 2, 'utf-8');
            $endOfString = mb_substr($string, 2, strlen($string));
        }

        return [$key1 => $firstTwoChars, $key2 => $endOfString];
    }


    /**
     * Returns the minimum DateTime for when the age is at least the inserted value.
     *
     * @param integer $years
     * @param boolean $accurateOnTheSecond
     * @return \DateTime
     */ //FIXME Fix exact age calculation
    public static function getDateLimitForAge($years, $accurateOnTheSecond = false)
    {
        if($accurateOnTheSecond == true) {
            $query = 'now';
        } else {
            $query = 'today';
        }
        $date = new \DateTime($query);
        $date->sub(new \DateInterval('P' . $years . "Y"));

        return $date;
    }

    /**
     * @param bool $accurateOnTheSecond if false it only looks at the day and not the hours, minutes and seconds
     * @return \DateTime the exact date at which someone or something becomes an adult
     */
    public static function getAdultDateOfBirthLimit($accurateOnTheSecond = false)
    {
        $adultAgeLimit = 1; //one year or older
        return self::getDateLimitForAge($adultAgeLimit, $accurateOnTheSecond);
    }


    /**
     * @param bool $accurateOnTheSecond if false it only looks at the day and not the hours, minutes and seconds
     * @return string
     */
    public static function getAdultDateStringOfBirthLimit($accurateOnTheSecond = false)
    {
        return self::getAdultDateOfBirthLimit($accurateOnTheSecond)->format('Y-m-d H:i:s');
    }
    

    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     *
     * @param int $length      How many characters do we want?
     * @param string $keyspace A string of all possible characters
     *                         to select from
     * @return string
     */
    public static function randomString($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * Verify whether all values in an array are unique.
     *
     * @param array $array
     * @return bool
     */
    public static function arrayValuesAreUnique($array)
    {
        for($i = 0; $i < sizeof($array); $i++) {
            for($j = $i +1; $j < sizeof($array); $j++) {
                if($array[$i] == $array[$j]) {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Verify whether all values in an array are unique.
     *
     * @param array $array
     * @return bool
     */
    public static function getPermutationProduct($array)
    {
        $arrayByIntKeys = array();
        foreach ($array as $item) {
            $arrayByIntKeys[] = $item;
        }

        $value = 0;
        $maxLength = sizeof($array);
        for($i = 0; $i < $maxLength; $i++) {

            for($j = $i+1; $j < $maxLength; $j++) {
                $value += $arrayByIntKeys[$i] * $arrayByIntKeys[$j];
            }
        }
        return $value;
    }
    

    /**
     * @param Collection $responses
     * @return mixed|null
     */
    public static function returnLastResponse(Collection $responses) {
        return self::returnLastItemFromCollectionByLogDate($responses); }


    /**
     * @param Collection $locationHealths
     * @return LocationHealth|null
     */
    public static function returnLastLocationHealth(Collection $locationHealths, $includeRevoked = false)
    {
        if($locationHealths->count() == 0) {
            return null;
        }

        if($includeRevoked == true) {
            return self::returnLastItemFromCollectionByLogDate($locationHealths);
        }

        $length = $locationHealths->count();

        //initialize values
        $lastItemIndex = 0;
        $startIndex = 0;

        $latestLogDate = null;

        //find the first LocationHealth that is not revoked
        foreach($locationHealths as $locationHealth) {
            if($locationHealth->getIsRevoked() == false) {
                $latestLogDate = $locationHealth->getLogDate();
                break;
            }
        }


        if($latestLogDate == null) {
            //no LocationHealths found that are not revoked
            return null;
        }

        for($i = $startIndex + 1; $i < $length; $i++) {
            $locationHealth = $locationHealths->get($i);
            $itemLogDate = $locationHealth->getLogDate();
            if($itemLogDate > $latestLogDate && $locationHealth->getIsRevoked() == false) {
                $lastItemIndex = $i;
                $latestLogDate = $itemLogDate;
            }
        }

        return $locationHealths->get($lastItemIndex);
    }

    /**
     * @param Collection $items
     * @return mixed|null
     */
    public static function returnLastItemFromCollectionByLogDate(Collection $items)
    {
        if($items->count() == 0) {
            return null;
        }

        $length = $items->count();

        //initialize values
        $lastItemIndex = 0;
        $startIndex = 0;
        $latestLogDate = $items->get($startIndex)->getLogDate();

        for($i = $startIndex + 1; $i < $length; $i++) {
            $itemLogDate = $items->get($i)->getLogDate();
            if($itemLogDate > $latestLogDate) {
                $lastItemIndex = $i;
                $latestLogDate = $itemLogDate;
            }
        }

        return $items->get($lastItemIndex);
    }


    /**
     * @param array $residences
     * @return AnimalResidence|null
     */
    public static function returnLastAnimalResidenceByStartDate(array $residences)
    {
        $length = sizeof($residences);

        if($length == 0) {
            return null;
        }

        //initialize values
        $lastItemIndex = 0;
        $startIndex = 0;
        $latestStartDate = $residences[$startIndex]->getStartDate();

        for($i = $startIndex + 1; $i < $length; $i++) {
            $itemStartDate = $residences[$i]->getStartDate();
            if($itemStartDate > $latestStartDate) {
                $lastItemIndex = $i;
                $latestStartDate = $itemStartDate;
            }
        }

        return $residences[$lastItemIndex];
    }
    
    
    /**
     * Weights are sorted first by measurementDate and then on logDate
     *
     * @param Animal $animal
     * @param ObjectManager $em
     * @return Weight|null
     */
    public static function returnLastWeightMeasurement(Animal $animal, ObjectManager $em)
    {

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->andWhere(Criteria::expr()->eq('isRevoked', false))
            ->orderBy(['measurementDate' => Criteria::DESC, 'logDate' => Criteria::DESC])
            ->setMaxResults(1);

        $weightMeasurementResult = $em->getRepository(Weight::class)
            ->matching($criteria);

        if($weightMeasurementResult->count() == 0) {
            $weightMeasurement = null;
        } else {
            $weightMeasurement = $weightMeasurementResult->get(0);
        }

        return $weightMeasurement;
    }

    /**
     * @param string $requestState
     * @return bool
     */
    public static function hasSuccessfulLastResponse($requestState)
    {
        if($requestState == RequestStateType::FINISHED){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param boolean $isValid
     * @param int $code
     * @param string $messageBody
     * @param array $optionalArray
     * @return array
     */
    public static function buildValidationArray($isValid, $code, $messageBody, $optionalArray = null)
    {
        $result = array(Constant::IS_VALID_NAMESPACE => $isValid,
            Constant::MESSAGE_NAMESPACE => array(Constant::CODE_NAMESPACE => $code,
                Constant::MESSAGE_NAMESPACE => $messageBody),
            Constant::CODE_NAMESPACE => $code,
        );

        if($optionalArray != null) {
            $result = array_merge($result, $optionalArray);
        }

        return $result;
    }

    /**
     * @param Animal $animal
     * @return string
     */
    public static function getUlnStringFromAnimal(Animal $animal)
    {
        return $animal->getUlnCountryCode() . $animal->getUlnNumber();
    }

    /**
     * @param ArrayCollection $content
     * @param string $key
     * @param string $defaultValue
     * @return mixed|null
     */
    public static function getValueFromArrayCollectionKeyIfItExists(ArrayCollection $content, $key, $defaultValue)
    {
        if($content->containsKey($key)) {
            $valueInKey = $content->get($key);
            if($valueInKey != null && $valueInKey != "") {
                return $valueInKey;
            } else {
                return $defaultValue;
            }
        } else {
            return $defaultValue;
        }
    }

    /**
     * @param array $locationHealthQueuesArray
     * @return LocationHealthQueue
     */
    public static function combineLocationHealthQueues($locationHealthQueuesArray)
    {
        $combinedLocationHealthQueue = new LocationHealthQueue();

        foreach($locationHealthQueuesArray as $locationHealthQueue) {

            foreach($locationHealthQueue->getArrivals() as $arrival) {
                $combinedLocationHealthQueue->addArrival($arrival);
            }

            foreach($locationHealthQueue->getImports() as $import) {
                $combinedLocationHealthQueue->addImport($import);
            }
        }

        return $combinedLocationHealthQueue;
    }

    /**
     * @param Animal $animal
     * @param Location $location
     * @param \DateTime $alternativeStartDate
     * @return Animal
     */
    public static function setResidenceToPending(Animal $animal, Location $location, \DateTime $alternativeStartDate)
    {
        $residenceList =  $animal->getAnimalResidenceHistory();
        $residenceToUpdate = $residenceList->last();

        if(!($residenceToUpdate instanceof AnimalResidence)) {
            //create a new animalResidence, if no animalResidences found
            //the startDate will likely not be available
            $residenceToUpdate = new animalResidence($location->getCountryCode(),true);
            $residenceToUpdate->setLocation($location);
            $residenceToUpdate->setAnimal($animal);
            $residenceToUpdate->setStartDate($alternativeStartDate);
            $animal->addAnimalResidenceHistory($residenceToUpdate);
        }

        if($residenceToUpdate->getLocation()->getUbn() == $location->getUbn()
          && $residenceToUpdate->getEndDate() == null) {
            //Set current residentState to pending
            $residenceToUpdate->setIsPending(true);
        }

        return $animal;
    }

    /**
     * Replace null values with empty strings for the frontend.
     * This only works with strings!
     *
     * @param string|null $value
     * @return string
     */
    public static function fillNull($value)
    {
        if($value === null) {
            return "";
        } else {
            return $value;
        }
    }

    /**
     * Replace null values and empty strings with replacement text.
     * This only works with strings!
     *
     * @param string|null $value
     * @return string
     */
    public static function fillNullOrEmptyString($value, $replacementText = "-")
    {
        if($value === null || $value === ""  || $value === " ") {
            return $replacementText;
        } else {
            return $value;
        }
    }


    /**
     * @param float $value
     * @return float
     */
    public static function fillNullOfFloatValue($value)
    {
        if($value === null || $value === ""  || $value === " ") {
            return 0.0;
        } else {
            return floatval($value);
        }
    }


    /**
     * Replace zeroes with replacement text.
     *
     * @param string|null $value
     * @return string
     */
    public static function fillZero($value, $replacementText = "-")
    {
        if($value === 0 || $value === 0.0 || $value === '0' || $value === '' || $value === ' '|| $value === null) {
            return $replacementText;
        } else {
            return $value;
        }
    }

    
    /**
     * Replace zeroes with replacement text.
     *
     * @param string|null $value
     * @return string
     */
    public static function fillZeroFloat($value, $replacementText = "-")
    {
        if(NumberUtil::isFloatZero($value) || $value === null) {
            return $replacementText;
        } else {
            return $value;
        }
    }
    

    /**
     * @param string $key
     * @param array $array
     * @return mixed|null
     */
    public static function getNullCheckedArrayValue($key, $array)
    {
        if($array === null || $key === null) { return null; }

        if(array_key_exists($key, $array)) {
            $value = $array[$key];
            if($value !== null && $value !== "") {
                return $value;
            }
        }

        return null;
    }


    /**
     * @param string $key
     * @param ArrayCollection $array
     * @return mixed|null
     */
    public static function getNullCheckedArrayCollectionValue($key, ArrayCollection $array)
    {
        if($array === null || $key === null) { return null; }

        if($array->containsKey($key)) {
            $value = $array->get($key);
            if($value !== null && $value !== "") {
                return $value;
            }
        }

        return null;
    }


    /**
     * @param string $key
     * @param array $array
     * @return mixed|null
     */
    public static function getNullCheckedArrayDateValue($key, $array)
    {
        if($array === null || $key === null) { return null; }

        $dateString = self::getNullCheckedArrayValue($key, $array);
        if($dateString !== null) {
            return new \DateTime($dateString);
        } else {
            return null;
        }
    }


    /**
     * @param string $key
     * @param ArrayCollection $array
     * @param bool $removeTime
     * @return mixed|null
     */
    public static function getNullCheckedArrayCollectionDateValue($key, ArrayCollection $array, $removeTime = false)
    {
        if($array === null || $key === null) { return null; }

        $dateString = self::getNullCheckedArrayCollectionValue($key, $array);
        if($dateString !== null) {
            $date = new \DateTime($dateString);
            if ($removeTime) {
                return new \DateTime($date->format('Y-m-d'));
            }
            return $date;
        } else {
            return null;
        }
    }


    /**
     * @param $string
     * @param int $totalInnerLength
     * @param int $marginSize
     * @param string $filler
     * @return string
     */
    public static function addPaddingToStringForColumnFormatCenter($string, $totalInnerLength, $marginSize = 2, $filler = " ")
    {
        $minimalPaddingOnBothSides = 1;
        if($marginSize < $minimalPaddingOnBothSides) {$marginSize = $minimalPaddingOnBothSides;}
        if($totalInnerLength < strlen($string)) {$totalInnerLength = strlen($string);}

        $innerPaddingSize = $totalInnerLength - strlen($string);
        $marginFiller = str_repeat($filler, $marginSize);

        if($innerPaddingSize <= 0) {
            $result = $marginFiller.$string.$marginFiller;
        } else {
            $innerFiller = str_repeat($filler, $innerPaddingSize);
            $result = $marginFiller.$innerFiller.$string.$marginFiller;
        }

        return $result;
    }


    /**
     * @param string $string
     * @param int $totalLength
     * @param bool $isLeftAligned
     * @param string $filler
     * @return string
     */
    public static function addPaddingToStringForColumnFormatSides($string, $totalLength, $isLeftAligned = true, $filler = " ")
    {
        if($totalLength-strlen($string) <= 0) { //string sticks out, or just fits. Add 1 extra padding on side facing table.
            if($isLeftAligned) {
                $result = $string.$filler;
            } else {
                $result = $filler.$string;
            }

        } else {
            $paddingSize = $totalLength - strlen($string);
            if($paddingSize < 0) {$paddingSize = 0;}

            if($isLeftAligned) {
                $result = $string.str_repeat($filler, $paddingSize);
            } else { //isRightAligned
                $result = str_repeat($filler, $paddingSize).$string;
            }
        }

        return $result;
    }


    /**
     * For example this string 'XL24AN55' will become the following array ['XL',24,'AN',55]
     * 
     * @param $string
     * @return array
     */
    public static function separateLettersAndNumbersOfString($string)
    {
        return is_string($string) ? preg_split("/(,?\s+)|((?<=[a-z])(?=\d))|((?<=\d)(?=[a-z]))/i", $string) : null;
    }


}