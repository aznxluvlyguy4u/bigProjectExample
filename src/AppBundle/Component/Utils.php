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
}