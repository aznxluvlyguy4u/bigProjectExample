<?php


namespace AppBundle\Util;


use AppBundle\Setting\MixBlupSetting;
use Doctrine\DBAL\Connection;

class CsvWriterUtil
{
    /**
     * @param Connection $conn
     * @param array $columnNames
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function maxStringLenghts(Connection $conn, array $columnNames)
    {
        $sql = "";
        $prefix = "";

        foreach ($columnNames as $column => $table)
        {
            $sql = $sql.$prefix."SELECT MAX(LENGTH(".$column.")) as max_length, '".$column."' as type FROM ".$table." ";
            $prefix = ' UNION ';
        }
        $nestedResults = $conn->query($sql)->fetchAll();

        $flatResults = [];
        foreach ($nestedResults as $nestedResult) {
            $flatResults[$nestedResult['type']] = $nestedResult['max_length'];
        }
        return $flatResults;
    }


    /**
     * @param string $string
     * @param int $padLength
     * @param boolean $useColumnPadding
     * @return string
     */
    public static function pad($string, $padLength, $useColumnPadding = true)
    {
        $paddedString = str_pad($string, $padLength, ' ', STR_PAD_RIGHT);
        if($useColumnPadding) {
            return $paddedString.self::spacing(MixBlupSetting::COLUMN_SPACING);
        }
        return str_pad($string, $padLength, ' ', STR_PAD_RIGHT);
    }


    /**
     * @param int $length
     * @param string $char
     * @return string
     */
    public static function spacing($length, $char = ' ')
    {
        return str_repeat($char, $length);
    }


    /**
     * @param array $data
     * @param int $columnWidth
     * @param string $key
     * @param bool $useColumnPadding
     * @param string $nullReplacement
     * @return string
     */
    public static function getFormattedValueFromArray($data, $columnWidth, $key, $useColumnPadding = true, $nullReplacement = null)
    {
        $value = ArrayUtil::get($key, $data, $nullReplacement);
        return CsvWriterUtil::pad($value, $columnWidth, $useColumnPadding);
    }
}