<?php


namespace AppBundle\Migration;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;

class InspectorMigrator
{
    /**
     * @param Connection $conn
     * @param array $csv
     * @return int
     */
    public static function fixInspectorNames(Connection $conn, $csv)
    {
        $namesSearchArray = self::createCorrectedNamesSearchArray($csv);

        $sql = "SELECT i.id, last_name FROM inspector i
                  INNER JOIN person p ON i.id = p.id
                  WHERE first_name ISNULL OR first_name = '' OR first_name = ' '
                ORDER BY last_name, first_name ASC ";
        $results = $conn->query($sql)->fetchAll();

        $totalCount = count($results);
        if($totalCount == 0) { return 0; }

        $updateCount = 0;

        foreach ($results as $result) {
            $id = $result['id'];
            $lastName = $result['last_name'];

            $newFirstName = null;
            $newLastName = null;
            
            $newNamesArray = ArrayUtil::get($lastName, $namesSearchArray);
            if(is_array($namesSearchArray)) {
                $newFirstName = $newNamesArray[JsonInputConstant::FIRST_NAME];
                $newLastName = $newNamesArray[JsonInputConstant::LAST_NAME];
            }

            if($newLastName != null && $newFirstName != null) {
                $sql = "UPDATE person SET first_name = '".$newFirstName."', last_name = '".$newLastName."'
                        WHERE id = ".$id;
                $conn->exec($sql);
                $updateCount++;
            }
        }

        return $updateCount;
    }


    /**
     * @param array $csv
     * @return array
     */
    public static function createCorrectedNamesSearchArray($csv)
    {
        $searchArray = [];
        foreach ($csv as $row) {
            $fullname = $row[0];
            $firstName = $row[1];
            $lastName = $row[2];
            $searchArray[$fullname] = [
              JsonInputConstant::FIRST_NAME => $firstName,
              JsonInputConstant::LAST_NAME => $lastName,
            ];
        }
        return $searchArray;
    }
}