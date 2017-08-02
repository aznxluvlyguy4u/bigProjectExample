<?php


namespace AppBundle\Service\DataFix;


use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

class AnimalExterminator extends DuplicateFixerBase
{
    /**
     * AnimalExterminator constructor.
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        parent::__construct($em);
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return bool
     */
    public function deleteAnimalsByCliInput(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);
        
        $option = $this->cmdUtil->generateMultiLineQuestion([
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
                    $animalId = $this->cmdUtil->generateMultiLineQuestion([
                        ' ', "\n",
                        'insert animalId: ', "\n",
                    ], self::DEFAULT_OPTION);
                    if(ctype_digit($animalId)) { $animalId = intval($animalId); }
                }
                return $this->deleteAnimalsAndAllRelatedRecordsByAnimalIds([$animalId]);
            case 2:
                $locationId = 0;
                while (!is_int($locationId) || $locationId ==0) {
                    $locationId = $this->cmdUtil->generateMultiLineQuestion([
                        ' ', "\n",
                        'insert locationId: ', "\n",
                    ], self::DEFAULT_OPTION);
                    if(ctype_digit($locationId)) { $locationId = intval($locationId); }
                }
                return $this->deleteAnimalsAndAllRelatedRecordsByLocationIds([$locationId]);
        }
    }


    /**
     * @param array $animalIds
     * @return bool
     */
    private function deleteAnimalsAndAllRelatedRecordsByAnimalIds(array $animalIds)
    {
        $totalNonAnimalRecordsDeleteCount = 0;
        $animalRecordsDeleteCount = 0;
        $skippedCount = 0;

        do{

            if($this->cmdUtil) { $this->cmdUtil->setStartTimeAndPrintIt(count($animalIds), 1); }
            foreach ($animalIds as $animalId) {
                $deleteCounts = $this->deleteRecordsInALlTablesContainingSelectedAnimalIds($animalId);
                $totalNonAnimalRecordsDeleteCount += $deleteCounts['non-animal'];
                $animalRecordsDeleteCount += $deleteCounts['animal'];
                $skippedCount += $deleteCounts['skipped'];
                if($this->cmdUtil) { $this->cmdUtil->advanceProgressBar(1, 'Deleted records animal|other: '.$animalRecordsDeleteCount.'|'.$totalNonAnimalRecordsDeleteCount.'  skipped: '.$skippedCount); }
            }
            if($this->cmdUtil) { $this->cmdUtil->setEndTimeAndPrintFinalOverview(); }

            //Reset values
            $totalNonAnimalRecordsDeleteCount = 0;
            $animalRecordsDeleteCount = 0;
            $skippedCount = 0;

        } while($totalNonAnimalRecordsDeleteCount + $animalRecordsDeleteCount != 0);

        return true;
    }


    /**
     * @param array $locationIds
     * @return bool
     */
    private function deleteAnimalsAndAllRelatedRecordsByLocationIds(array $locationIds)
    {
        $totalNonAnimalRecordsDeleteCount = 0;
        $animalRecordsDeleteCount = 0;
        $skippedCount = 0;

        do{

            if($this->cmdUtil) { $this->cmdUtil->setStartTimeAndPrintIt(count($locationIds), 1); }
            foreach ($locationIds as $locationId) {
                $deleteCounts = $this->deleteRecordsInALlTablesContainingSelectedAnimalIds($locationId, true);
                $totalNonAnimalRecordsDeleteCount += $deleteCounts['non-animal'];
                $animalRecordsDeleteCount += $deleteCounts['animal'];
                $skippedCount += $deleteCounts['skipped'];
                if($this->cmdUtil) { $this->cmdUtil->advanceProgressBar(1, 'Deleted records animal|other: '.$animalRecordsDeleteCount.'|'.$totalNonAnimalRecordsDeleteCount.'  skipped: '.$skippedCount); }
            }
            if($this->cmdUtil) { $this->cmdUtil->setEndTimeAndPrintFinalOverview(); }

            //Reset values
            $totalNonAnimalRecordsDeleteCount = 0;
            $animalRecordsDeleteCount = 0;
            $skippedCount = 0;

        } while($totalNonAnimalRecordsDeleteCount + $animalRecordsDeleteCount != 0);

        return true;
    }


    /**
     * @param int $id
     * @param boolean $isLocationId
     * @throws \Doctrine\DBAL\DBALException
     * @return boolean|array
     *
     */
    private function deleteRecordsInALlTablesContainingSelectedAnimalIds($id, $isLocationId = false)
    {
        if(!is_int($id)) { return false; }

        //Check in which tables have the secondaryAnimalId
        $tableNamesByVariableType = [
            [ self::TABLE_NAME => 'animal_cache',           self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'breed_index',            self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'breed_value',            self::VARIABLE_TYPE => 'animal_id' ],
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
            [ self::TABLE_NAME => 'result_table_breed_grades',  self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'worm_resistance',        self::VARIABLE_TYPE => 'animal_id' ],

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
                $deleteCount = $this->conn->query($sql)->fetch()['count'];

                if($tableName == 'animal') {
                    $animalRecordsDeleteCount += $deleteCount;
                } else {
                    $totalNonAnimalRecordsDeleteCount += $deleteCount;
                }

            } catch (\Exception $exception) {

                if($isLocationId && $tableName == 'animal') {
                    //Delete animals one by one
                    $sql = "SELECT id FROM animal WHERE location_id = ".$id;
                    $animalIdsResult = $this->conn->query($sql)->fetchAll();

                    foreach ($animalIdsResult as $animalIdResult) {
                        $animalIdOnLocation = $animalIdResult['id'];

                        try{
                            $sql = "WITH rows AS (
                                    DELETE FROM animal WHERE id = " .$animalIdOnLocation. "
                                RETURNING 1
                                )
                                SELECT COUNT(*) AS count FROM rows;
                                ";
                            $deleteCount = $this->conn->query($sql)->fetch()['count'];
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