<?php


namespace AppBundle\Util;


/**
 * Class ArrayUtil
 *
 * @ORM\Entity(repositoryClass="AppBundle\Util")
 * @package AppBundle\Util
 */
class ArrayUtil
{
    /**
     * Get null checked value value from an array
     * 
     * @param $key
     * @param array $array
     * @param mixed $nullReplacement
     * @return mixed|null
     */
    public static function get($key, array $array, $nullReplacement = null)
    {
        if(array_key_exists($key, $array)) {
            if($array[$key] !== null) {
                return $array[$key];
            }
        }
        return $nullReplacement;
    }


    /**
     * @param array $arrays
     * @return array
     */
    public static function concatArrayValues(array $arrays)
    {
        $combinedArray = [];

        foreach ($arrays as $array) {
            foreach ($array as $value) {
                $combinedArray[] = $value;
            }
        }

        return $combinedArray;
    }
}