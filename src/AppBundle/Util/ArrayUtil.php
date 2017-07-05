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
     * @param boolean $ignoreAllKeys This prevents overwriting values with identical keys, but you lose the keys.
     * @return array
     */
    public static function concatArrayValues(array $arrays, $ignoreAllKeys = true)
    {
        $combinedArray = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if(!is_int($key) && !$ignoreAllKeys) {
                    $combinedArray[$key] = $value;
                } else {
                    $combinedArray[] = $value;
                }
            }
        }

        return $combinedArray;
    }


    /**
     * @param $key
     * @param array $array
     * @return int
     */
    public static function keyPosition($key, $array)
    {
        return array_search($key, array_keys($array));
    }
}