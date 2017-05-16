<?php


namespace AppBundle\Util;


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
}