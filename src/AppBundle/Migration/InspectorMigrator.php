<?php


namespace AppBundle\Migration;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
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


    /**
     * @param Connection $conn
     * @param InspectorRepository $inspectorRepository
     * @param $csv
     * @return int
     */
    public static function addMissingInspectors(Connection $conn, $inspectorRepository, $csv)
    {
        $newInspectorCount = 0;

        foreach ($csv as $row) {
            $firstName = $row[0];
            $lastName = $row[1];
            $newInspectorCount += self::addMissingInspector($conn, $inspectorRepository, $firstName, $lastName);
        }

        return $newInspectorCount;
    }


    /**
     * Return number of new inspectors added.
     * @param Connection $conn
     * @param InspectorRepository $inspectorRepository
     * @param string $firstName
     * @param string $lastName
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function addMissingInspector(Connection $conn, $inspectorRepository, $firstName, $lastName)
    {
        $sql = "SELECT COUNT(*) FROM inspector i
                  INNER JOIN person p ON i.id = p.id
                WHERE first_name = '".$firstName."' AND last_name = '".$lastName."'";
        $count = $conn->query($sql)->fetch()['count'];

        if($count == 0) {
            $inspectorRepository->insertNewInspector($firstName, $lastName);
            return 1;
        }
        return 0;
    }


    /**
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     * @param InspectorRepository $inspectorRepository
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function fixDuplicateInspectors(Connection $conn, CommandUtil $cmdUtil, InspectorRepository $inspectorRepository)
    {
        $sql = "SELECT x.id, x.first_name, x.last_name FROM person x
                INNER JOIN (
                    SELECT p.first_name, p.last_name, p.type FROM inspector i
                      INNER JOIN person p ON i.id = p.id
                      WHERE p.type = 'Inspector'
                    GROUP BY p.first_name, p.last_name, p.type HAVING COUNT(*) > 1
                    )y ON y.first_name = x.first_name AND y.last_name = x.last_name AND y.type = x.type";
        $results =$conn->query($sql)->fetchAll();

        $groupedSearchArray = [];
        foreach ($results as $result) {
            $id = $result['id'];
            $firstName = $result['first_name'];
            $lastName = $result['last_name'];
            $searchKey = $firstName.'__'.$lastName;

            if(array_key_exists($searchKey, $groupedSearchArray)) {
                $group = $groupedSearchArray[$searchKey];
            } else {
                $group = [];
            }

            $group[] = $result;
            $groupedSearchArray[$searchKey] = $group;
        }

        $totalDuplicateCount = count($groupedSearchArray);
        if($totalDuplicateCount == 0) {
            $cmdUtil->writeln('No duplicate inspectors!');
            return;
        }

        $cmdUtil->setStartTimeAndPrintIt($totalDuplicateCount, 1);

        foreach ($groupedSearchArray as $group) {
            $firstInspectorResult = $group[0];
            $primaryInspectorId = $firstInspectorResult['id'];
            foreach ($group as $result) {
                $secondaryInspectorId = $result['id'];
                if($primaryInspectorId != $secondaryInspectorId) {
                    $sql = "UPDATE measurement SET inspector_id = ".$primaryInspectorId." WHERE inspector_id = ".$secondaryInspectorId;
                    $conn->exec($sql);

                    $sql = "INSERT INTO data_import_string_replacement (id, primary_string, secondary_string, type) VALUES (nextval('data_import_string_replacement_id_seq'),'" .$primaryInspectorId. "','" . $secondaryInspectorId . "','Inspector')";
                    $conn->exec($sql);

                    $inspectorRepository->deleteInspector($secondaryInspectorId);
                }
            }
            $cmdUtil->advanceProgressBar(1, 'Removing duplicate inspectors');
        }
        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }
}