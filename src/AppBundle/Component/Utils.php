<?php

namespace AppBundle\Component;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\WeightMeasurement;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
     * validate if Id is of format: AZ123456789
     *
     * @param $ulnString
     * @return bool
     */
    public static function verifyUlnFormat($ulnString)
    {
        if(preg_match("([A-Z]{2}\d+)",$ulnString)) {
            return true;
        }
        return false;
    }

    public static function getUlnFromString($ulnString)
    {
        //Verify format first
        if(!Utils::verifyUlnFormat($ulnString)) {
            return null;
        }

        $countryCode = mb_substr($ulnString, 0, 2, 'utf-8');
        $ulnNumber = mb_substr($ulnString, 2, strlen($ulnString));

        return array(Constant::ULN_COUNTRY_CODE_NAMESPACE => $countryCode, Constant::ULN_NUMBER_NAMESPACE => $ulnNumber);
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
     * @param Collection $responses
     * @return mixed|null
     */
    public static function returnLastResponse(Collection $responses)
    {
        return self::returnLastItemFromCollectionByLogDate($responses);
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
     * WeightMeasurement are sorted first by weightMeasurementDate and then on logDate
     *
     * @param Collection $weightMeasurements
     * @return WeightMeasurement|null
     */
    public static function returnLastWeightMeasurement(Collection $weightMeasurements)
    {
        if($weightMeasurements->count() == 0) {
            return null;
        }

        $length = $weightMeasurements->count();

        $measurementsOnLastWeightMeasurementDate = new ArrayCollection();

        //initialize values
        $startIndex = 0;
        $firstWeightMeasurement = $weightMeasurements->get($startIndex);
        $latestWeightMeasurementDate = $firstWeightMeasurement->getWeightMeasurementDate();
        $measurementsOnLastWeightMeasurementDate->add($firstWeightMeasurement);

        //Gather the weightMeasurements with the latest weightMeasurementDate
        for($i = $startIndex + 1; $i < $length; $i++) {
            $weightMeasurement = $weightMeasurements->get($i);
            $weightMeasurementDate = $weightMeasurement->getWeightMeasurementDate();

            if($weightMeasurementDate > $latestWeightMeasurementDate) {
                $measurementsOnLastWeightMeasurementDate->clear();
                $measurementsOnLastWeightMeasurementDate->add($weightMeasurement);
                $latestWeightMeasurementDate = $weightMeasurementDate;

            } else if($weightMeasurementDate == $latestWeightMeasurementDate) {
                $measurementsOnLastWeightMeasurementDate->add($weightMeasurement);
            }
        }

        //Then find the one with the latest logDate
        return self::returnLastItemFromCollectionByLogDate($measurementsOnLastWeightMeasurementDate);
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

}