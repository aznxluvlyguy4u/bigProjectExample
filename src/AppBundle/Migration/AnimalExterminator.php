<?php


namespace AppBundle\Migration;


use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Config\Definition\Exception\Exception;

class AnimalExterminator
{
    const VARIABLE_TYPE = 'variable_type';
    const TABLE_NAME = 'table_name';

    const DEFAULT_OPTION = 0;

    /**
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @return bool
     */
    public static function deleteAnimalsByCliInput(ObjectManager $em, CommandUtil $cmdUtil)
    {
        $option = $cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            'Delete animal and all related records by...', "\n",
            '1: AnimalId', "\n",
            '2: LocationId', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $animalId = 0;
                while (!is_int($animalId) || $animalId ==0) {
                    $animalId = $cmdUtil->generateMultiLineQuestion([
                        ' ', "\n",
                        'insert animalId: ', "\n",
                    ], self::DEFAULT_OPTION);
                    if(ctype_digit($animalId)) { $animalId = intval($animalId); }
                }
                return self::deleteAnimalsAndAllRelatedRecordsByAnimalIds($em, [$animalId], $cmdUtil);
            case 2:
                $locationId = 0;
                while (!is_int($locationId) || $locationId ==0) {
                    $locationId = $cmdUtil->generateMultiLineQuestion([
                        ' ', "\n",
                        'insert locationId: ', "\n",
                    ], self::DEFAULT_OPTION);
                    if(ctype_digit($locationId)) { $locationId = intval($locationId); }
                }
                return self::deleteAnimalsAndAllRelatedRecordsByLocationIds($em, [$locationId], $cmdUtil);
        }
    }
    
    
    /**
     * @param ObjectManager $em
     * @param array $animalIds
     * @param CommandUtil $cmdUtil
     * @return bool
     */
    public static function deleteAnimalsAndAllRelatedRecordsByAnimalIds(ObjectManager $em, array $animalIds, CommandUtil $cmdUtil = null)
    {
        $totalNonAnimalRecordsDeleteCount = 0;
        $animalRecordsDeleteCount = 0;
        $skippedCount = 0;

        do{

            if($cmdUtil) { $cmdUtil->setStartTimeAndPrintIt(count($animalIds), 1); }
            foreach ($animalIds as $animalId) {
                $deleteCounts = self::deleteRecordsInALlTablesContainingSelectedAnimalIds($em, $animalId);
                $totalNonAnimalRecordsDeleteCount += $deleteCounts['non-animal'];
                $animalRecordsDeleteCount += $deleteCounts['animal'];
                $skippedCount += $deleteCounts['skipped'];
                if($cmdUtil) { $cmdUtil->advanceProgressBar(1, 'Deleted records animal|other: '.$animalRecordsDeleteCount.'|'.$totalNonAnimalRecordsDeleteCount.'  skipped: '.$skippedCount); }
            }
            if($cmdUtil) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }

            //Reset values
            $totalNonAnimalRecordsDeleteCount = 0;
            $animalRecordsDeleteCount = 0;
            $skippedCount = 0;

        } while($totalNonAnimalRecordsDeleteCount + $animalRecordsDeleteCount != 0);
        
        return true;
    }


    /**
     * @param ObjectManager $em
     * @param array $locationIds
     * @param CommandUtil $cmdUtil
     * @return bool
     */
    public static function deleteAnimalsAndAllRelatedRecordsByLocationIds(ObjectManager $em, array $locationIds, CommandUtil $cmdUtil= null)
    {
        $totalNonAnimalRecordsDeleteCount = 0;
        $animalRecordsDeleteCount = 0;
        $skippedCount = 0;

        do{

            if($cmdUtil) { $cmdUtil->setStartTimeAndPrintIt(count($locationIds), 1); }
            foreach ($locationIds as $locationId) {
                $deleteCounts = self::deleteRecordsInALlTablesContainingSelectedAnimalIds($em, $locationId, true);
                $totalNonAnimalRecordsDeleteCount += $deleteCounts['non-animal'];
                $animalRecordsDeleteCount += $deleteCounts['animal'];
                $skippedCount += $deleteCounts['skipped'];
                if($cmdUtil) { $cmdUtil->advanceProgressBar(1, 'Deleted records animal|other: '.$animalRecordsDeleteCount.'|'.$totalNonAnimalRecordsDeleteCount.'  skipped: '.$skippedCount); }
            }
            if($cmdUtil) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }

            //Reset values
            $totalNonAnimalRecordsDeleteCount = 0;
            $animalRecordsDeleteCount = 0;
            $skippedCount = 0;

        } while($totalNonAnimalRecordsDeleteCount + $animalRecordsDeleteCount != 0);

        return true;
    }


    /**
     * @param ObjectManager $em
     * @param int $id
     * @param boolean $isLocationId
     * @throws \Doctrine\DBAL\DBALException
     * @return boolean
     *
     */
    private static function deleteRecordsInALlTablesContainingSelectedAnimalIds(ObjectManager $em, $id, $isLocationId = false)
    {
        /** @var Connection $conn */
        $conn = $em->getConnection();

        if(!is_int($id)) { return false; }

        //Check in which tables have the secondaryAnimalId
        $tableNamesByVariableType = [
            [ self::TABLE_NAME => 'animal_cache',           self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_arrival',        self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_export',         self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_import',         self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_depart',         self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_tag_replace',    self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_loss',           self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_birth',          self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'exterior',               self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'body_fat',               self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'weight',                 self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'muscle_thickness',       self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'tail_length',            self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_weight',         self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'mate',                   self::VARIABLE_TYPE => 'stud_ram_id' ],
            [ self::TABLE_NAME => 'mate',                   self::VARIABLE_TYPE => 'stud_ewe_id' ],
            [ self::TABLE_NAME => 'animal',                 self::VARIABLE_TYPE => 'parent_mother_id' ],
            [ self::TABLE_NAME => 'animal',                 self::VARIABLE_TYPE => 'parent_father_id' ],
            [ self::TABLE_NAME => 'litter',                 self::VARIABLE_TYPE => 'animal_mother_id' ],
            [ self::TABLE_NAME => 'litter',                 self::VARIABLE_TYPE => 'animal_father_id' ],
            [ self::TABLE_NAME => 'animal_residence',       self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'breed_values_set',       self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'ulns_history',           self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'blindness_factor',       self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'predicate',              self::VARIABLE_TYPE => 'animal_id' ],

            //This should be the last row!
            [ self::TABLE_NAME => 'animal',              self::VARIABLE_TYPE => 'id' ],
        ];

        $totalNonAnimalRecordsDeleteCount = 0;
        $animalRecordsDeleteCount = 0;
        $skippedCount = 0;
        foreach ($tableNamesByVariableType as $tableNameByVariableType) {
            $tableName = $tableNameByVariableType[self::TABLE_NAME];
            $variableType = $tableNameByVariableType[self::VARIABLE_TYPE];

            try {
                if(!$isLocationId) {
                    $filter = " = ".$id." ";
                } else {
                    $filter = " IN( SELECT id FROM animal WHERE location_id = ".$id." ) ";
                }

                $sql = "WITH rows AS (
                      DELETE FROM " . $tableName . "
                        WHERE " . $variableType . " " . $filter . "
                      RETURNING 1
                    )
                    SELECT COUNT(*) AS count FROM rows;
                    ";
                $deleteCount = $conn->query($sql)->fetch()['count'];

                if($tableName == 'animal') {
                    $animalRecordsDeleteCount += $deleteCount;
                } else {
                    $totalNonAnimalRecordsDeleteCount += $deleteCount;
                }

            } catch (\Exception $exception) {

                if($isLocationId && $tableName == 'animal') {
                    //Delete animals one by one
                    $sql = "SELECT id FROM animal WHERE location_id = ".$id;
                    $animalIdsResult = $conn->query($sql)->fetchAll();

                    foreach ($animalIdsResult as $animalIdResult) {
                        $animalIdOnLocation = $animalIdResult['id'];

                        try{
                            $sql = "WITH rows AS (
                                    DELETE FROM animal WHERE id = " .$animalIdOnLocation. "
                                RETURNING 1
                                )
                                SELECT COUNT(*) AS count FROM rows;
                                ";
                            $deleteCount = $conn->query($sql)->fetch()['count'];
                            $animalRecordsDeleteCount += $deleteCount;

                        } catch (\Exception $e) {
                            $skippedCount++;
                        }
                    }

                } else {
                    $skippedCount++;
                }
            }
        }

        return [
            'animal' => $animalRecordsDeleteCount,
            'non-animal' => $totalNonAnimalRecordsDeleteCount,
            'skipped' => $skippedCount,
        ];
    }


}