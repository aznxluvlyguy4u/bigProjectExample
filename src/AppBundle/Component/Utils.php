<?php

namespace AppBundle\Component;
use AppBundle\Constant\Constant;

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
}