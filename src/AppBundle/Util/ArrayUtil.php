<?php


namespace AppBundle\Util;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class ArrayUtil
 *
 * @ORM\Entity(repositoryClass="AppBundle\Util")
 * @package AppBundle\Util
 */
class ArrayUtil
{
    const KEY_VALUE_SEPARATOR = ' => ';

    /** @var int|string */
    private static $nestedKey;

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
     * @param array $values
     * @param array $array
     * @return bool
     */
    public static function hasAtLeastOneValueInArray(array $values, array $array): bool
    {
        foreach ($values as $value) {
            if (in_array($value, $array)) {
                return true;
            }
        }
        return false;
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


    /**
     * @param array $array
     * @param bool $allowResetingPointer
     * @return mixed|null
     */
    public static function firstValue(array $array, $allowResetingPointer = true)
    {
        if (count($array) === 0) { return null; }

        if ($allowResetingPointer) {
            return reset($array);
        }

        return array_values($array)[0];
    }


    /**
     * @param array $array
     * @param bool $allowResetingPointer
     * @return mixed|null
     */
    public static function firstKey(array $array, $allowResetingPointer = true)
    {
        if (count($array) === 0) { return null; }

        if ($allowResetingPointer) {
            reset($array);
            return key($array);
        }

        return array_keys($array)[0];
    }


    /**
     * @param array $array
     * @param bool $resetPointer
     * @return mixed|null
     */
    public static function lastKey(array $array, $resetPointer = true)
    {
        if (count($array) === 0) { return null; }

        end($array); //Move pointer to last element
        $lastKey = key($array);

        if ($resetPointer) {
            reset($array); //Move pointer to first element
        }

        return $lastKey;
    }


    /**
     * @param array|ArrayCollection $array
     * @param string $keyValueSeparator
     * @return string
     */
    public static function implode($array, $keyValueSeparator = self::KEY_VALUE_SEPARATOR)
    {
        if ($array instanceof ArrayCollection) {
            $array = $array->toArray();
        }

        if (is_string($array)) { return $array; }

        $string = '[';
        $prefix = '';
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::implode($value, $keyValueSeparator);
            }

            $string = $string . $prefix . $key . $keyValueSeparator . $value;
            $prefix = ', ';
        }
        $string = $string . ']';

        return $string;
    }


    /**
     * @param array $array
     * @return array
     */
    public static function removeEmptyValues($array)
    {
        return array_filter($array, function($a) { return $a !== null; });
    }


    /**
     * @param string|int $nestedKey
     * @param array $array
     * @param bool $removeNullValues
     * @return array
     */
    public static function mapNestedValues($nestedKey, $array, $removeNullValues = false)
    {
        self::$nestedKey = $nestedKey;

        $values = array_map(
            function ($record) {
                return ArrayUtil::get(self::$nestedKey, $record);
            },
            $array
        );

        self::$nestedKey = null;

        return $removeNullValues ? self::removeEmptyValues($values) : $values;
    }


    /**
     * @param $array
     * @param array $keys
     * @return array
     */
    public static function removeKeys($array, $keys = array())
    {
        if(empty($array) || (!is_array($array))) {
            return $array;
        }

        if(is_string($keys)) {
            $keys = explode(',', $keys);
        }

        if(!is_array($keys) || count($array) === 0) {
            return $array;
        }

        $assocKeys = array();
        foreach($keys as $key) {
            $assocKeys[$key] = true;
        }

        return array_diff_key($array, $assocKeys);
    }


    /**
     * @param array $needles
     * @param array $haystack
     * @param boolean $hayStackIsSetOfArrays
     * @throws \Exception
     */
    public static function validateIfKeysExist($needles, $haystack, $hayStackIsSetOfArrays)
    {
        if (count($needles) === 0) {
            return;
        }

        if ($hayStackIsSetOfArrays) {
            $haystack = reset($haystack);
        }

        if ($haystack === false || count($haystack) === 0) {
            throw new \Exception('DATA IS EMPTY', Response::HTTP_BAD_REQUEST);
        }

        $missingKeys = [];
        foreach ($needles as $needle)
        {
            if (!key_exists($needle, $haystack)) {
                $missingKeys[] = $needle;
            }
        }

        if (count($missingKeys) > 0) {
            throw new \Exception('Array is missing the following keys: '.implode(', ', $missingKeys));
        }
    }
}