<?php


namespace AppBundle\Util;


class SqlUtil
{
    const NULL = 'NULL';
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @param mixed $value
     * @param boolean $includeQuotes
     * @return string
     */
    public static function getNullCheckedValueForSqlQuery($value, $includeQuotes)
    {
        if($value != null && $value != '') {
            return $includeQuotes ? StringUtil::getNullAsStringOrWrapInQuotes($value) : StringUtil::getNullAsString($value);
        } else {
            return self::NULL;
        }
    }


    /**
     * @param mixed $value
     * @param array $searchArray
     * @param boolean $includeQuotes
     * @return string
     */
    public static function getSearchArrayCheckedValueForSqlQuery($value, $searchArray, $includeQuotes)
    {
        if(array_key_exists($value, $searchArray)) {
            return $includeQuotes ? StringUtil::getNullAsStringOrWrapInQuotes($searchArray[$value]) : StringUtil::getNullAsString($searchArray[$value]);
        } else {
            return self::NULL;
        }
    }


    /**
     * @param array $sqlResultsArray
     * @param string $variable1ToGroupBy
     * @param string $variable2ToGroupBy
     * @return array
     */
    public static function createGroupedSearchArrayFromSqlResults($sqlResultsArray, $variable1ToGroupBy, $variable2ToGroupBy = null)
    {
        $duplicatesSearchArray = [];
        //Create grouped searchArray
        foreach ($sqlResultsArray as $result) {

            $keyToGroupBy = $result[$variable1ToGroupBy];
            if($variable2ToGroupBy != null) {
                $keyToGroupBy = $result[$variable1ToGroupBy].$result[$variable2ToGroupBy];
            }

            if(!array_key_exists($keyToGroupBy, $duplicatesSearchArray)) {
                $duplicatesSearchArray[$keyToGroupBy] = [];
            }

            $group = $duplicatesSearchArray[$keyToGroupBy];
            $group[] = $result;
            $duplicatesSearchArray[$keyToGroupBy] = $group;
        }
        return $duplicatesSearchArray;
    }
}