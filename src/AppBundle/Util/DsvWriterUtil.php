<?php


namespace AppBundle\Util;


use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Setting\MixBlupSetting;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

class DsvWriterUtil
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
        return DsvWriterUtil::pad($value, $columnWidth, $useColumnPadding);
    }


    /**
     * @param array $records
     * @param string $filepath
     * @throws \Exception
     */
    public static function writeToFile(array $records, $filepath)
    {
        $lastKey = self::writeToFileBase($records, $filepath);

        $newLine = "\n";
        foreach ($records as $key => $record) {
            if($key === $lastKey) { $newLine = ''; }
            file_put_contents($filepath, $record.$newLine, FILE_APPEND);
        }
    }


    /**
     * @param array $records
     * @param string $filepath
     * @param string $separatorSymbol
     * @throws \Exception
     */
    public static function writeNestedRecordToFile(array $records, $filepath, $separatorSymbol = CsvOptions::DEFAULT_SEPARATOR)
    {
        $lastKey = self::writeToFileBase($records, $filepath);

        DsvWriterUtil::writeNestedRowToFile($filepath, array_keys(ArrayUtil::firstValue($records, true))); //write headers

        $newLine = "\n";
        foreach ($records as $key => $values) {
            if($key === $lastKey) { $newLine = ''; }
            $record = implode($separatorSymbol, $values);
            file_put_contents($filepath, $record.$newLine, FILE_APPEND);
        }
    }


    /**
     * @param string $filepath
     * @param array $values
     * @param string $separator
     */
    public static function writeNestedRowToFile($filepath, array $values, $separator = CsvOptions::DEFAULT_SEPARATOR)
    {
        file_put_contents($filepath, implode($separator, $values) ."\n",FILE_APPEND);
    }


    /**
     * @param array $records
     * @param string $filepath
     * @return string|int lastKey as $records
     * @throws \Exception
     */
    private static function writeToFileBase(array $records, $filepath)
    {
        if(!is_string($filepath) || $filepath == '') {
            throw new \Exception('INVALID FILEPATH FOR CSV GENERATION', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if(count($records) === 0) {
            throw new \Exception('DATA IS EMPTY', Response::HTTP_BAD_REQUEST);
        }

        NullChecker::createFolderPathIfNull(dirname($filepath));

        //purge current file content
        file_put_contents($filepath, "");

        return ArrayUtil::lastKey($records);
    }
}