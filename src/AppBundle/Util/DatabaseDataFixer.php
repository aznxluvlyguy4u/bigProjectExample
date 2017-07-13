<?php


namespace AppBundle\Util;


use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseDataFixer
{

    /**
     * @param Connection $conn
     * @param CommandUtil|OutputInterface $cmdUtilOrOutputInterface
     * @return int $count
     */
    public static function fixIncongruentAnimalOrderNumbers(Connection $conn, $cmdUtilOrOutputInterface = null)
    {
        $sql = "WITH rows AS (
                  UPDATE animal SET animal_order_number = SUBSTRING(uln_number, LENGTH(uln_number) - 4)
                    WHERE animal_order_number <> SUBSTRING(uln_number, LENGTH(uln_number) - 4) OR animal_order_number ISNULL
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows";
        $count = $conn->query($sql)->fetch()['count'];

        if($cmdUtilOrOutputInterface != null) {
            $message = $count == 0 ? 'All animalOrderNumbers were already correct!' : $count . ' animalOrderNumbers fixed!';
            $cmdUtilOrOutputInterface->writeln($message);
        }

        return $count;
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|null $cmdUtil
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateMaxIdOfAllSequences(Connection $conn, CommandUtil $cmdUtil = null)
    {
        $sql = "SELECT c.relname as sequence_name FROM pg_class c WHERE c.relkind = 'S';";
        $sequenceNames = $conn->query($sql)->fetchAll();

        $tableNames = [];
        $removeCharCount = strlen('_id_seq');
        foreach ($sequenceNames as $sequenceName) {
            $tableNames[] = StringUtil::removeStringEnd($sequenceName['sequence_name'], $removeCharCount);
        }

        foreach ($tableNames as $tableName) {
            $sql = "SELECT setval('".$tableName."_id_seq', (SELECT MAX(id) FROM ".$tableName."))";
            $newMaxIdInSequence = $conn->query($sql)->fetch()['setval'];
//            $sql = "ALTER TABLE ".$tableName." ALTER id SET DEFAULT nextval('".$tableName."_id_seq')";
//            $newMaxIdInSequence = $conn->exec($sql);

            if($cmdUtil) { $cmdUtil->writeln($tableName.' maxId sequence = '.$newMaxIdInSequence); }
        }
    }


    /**
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function fixGenderTables(Connection $conn, CommandUtil $cmdUtil)
    {
        /* Diagnosis */

        $sql = "SELECT animal.id, ram.object_type, animal.type FROM animal INNER JOIN ram ON animal.id = ram.id WHERE ram.object_type <> animal.type";
        $resultsRam = $conn->query($sql)->fetchAll();
        self::printDiagnosisResultsOfFixGenderTables($cmdUtil, $resultsRam, 'Ram');

        $sql = "SELECT animal.id, ewe.object_type, animal.type FROM animal INNER JOIN ewe ON animal.id = ewe.id WHERE ewe.object_type <> animal.type";
        $resultsEwe = $conn->query($sql)->fetchAll();
        self::printDiagnosisResultsOfFixGenderTables($cmdUtil, $resultsEwe, 'Ewe');

        $sql = "SELECT animal.id, neuter.object_type, animal.type FROM animal INNER JOIN neuter ON animal.id = neuter.id WHERE neuter.object_type <> animal.type";
        $resultsNeuter = $conn->query($sql)->fetchAll();
        self::printDiagnosisResultsOfFixGenderTables($cmdUtil, $resultsNeuter, 'Neuter');

        $cmdUtil->writeln([' ', 'NOTE! RERUN THIS COMMAND AFTER EVERY DUPLICATE KEY VIOLATION ERROR' ,' ']);

        $cmdUtil->setStartTimeAndPrintIt(count($resultsRam) + count($resultsEwe) + count($resultsNeuter), 1);

        if(count($resultsRam) > 0) {
            /* Fix animals incorrectly being a Ram */
            foreach($resultsRam as $ramResult) {
                $id = $ramResult['id'];
                self::genderTableFixSqlCommand($conn, $id, 'ram', $ramResult['type']);
                $cmdUtil->advanceProgressBar(1, 'Incorrect Ram with id: '.$id.'');
            }
        }

        if(count($resultsEwe) > 0) {
            /* Fix animals incorrectly being a Ewe */
            foreach($resultsEwe as $eweResult) {
                $id = $eweResult['id'];
                self::genderTableFixSqlCommand($conn, $id, 'ewe', $eweResult['type']);
                $cmdUtil->advanceProgressBar(1, 'Incorrect Ewe with id: '.$id.'');
            }
        }


        if(count($resultsNeuter) > 0) {
            /* Fix animals incorrectly being a Neuter */
            foreach($resultsNeuter as $neuterResult) {
                $id = $neuterResult['id'];
                self::genderTableFixSqlCommand($conn, $id, 'neuter', $neuterResult['type']);
                $cmdUtil->advanceProgressBar(1, 'Incorrect Neuter with id: '.$id.'');
            }
        }
        
        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param Connection $conn
     * @param $id
     * @param $oldTableName
     * @param $newTableName
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function genderTableFixSqlCommand(Connection $conn, $id, $oldTableName, $newTableName)
    {
        $sql = "DELETE FROM ".strtolower($oldTableName)." WHERE id = '".$id."'";
        $conn->exec($sql);
        $sql = "SELECT COUNT(*) = 0 as is_empty FROM ".strtolower($newTableName)." WHERE id = ".$id;
        $isEmpty = boolval($conn->query($sql)->fetch()['is_empty']);
        if($isEmpty) {
            $sql = "INSERT INTO ".strtolower($newTableName)." (id, object_type) VALUES ('".$id."', '".ucfirst(strtolower($newTableName))."')";
            $conn->exec($sql);   
        }
    }


    /**
     * @param CommandUtil|OutputInterface $cmdUtilOrOutput
     * @param $results
     * @param $entityType
     */
    private static function printDiagnosisResultsOfFixGenderTables($cmdUtilOrOutput, $results, $entityType)
    {
        $neuterCount = 0;
        $eweCount = 0;
        $ramCount = 0;
        foreach ($results as $result) {
            if($result['type'] == 'Ewe') { $eweCount++; }
            elseif($result['type'] == 'Neuter') { $neuterCount++; }
            elseif($result['type'] == 'Ram') { $ramCount++; }
        }

        $cmdUtilOrOutput->writeln([
            ' ',
            '=== '.$entityType.' Entities ===',
            'Total: '.count($results)
        ]);

        if($entityType != 'Ewe') {
            $cmdUtilOrOutput->writeln('As Ewe type in Animal: '.$eweCount);
        }

        if($entityType != 'Ram') {
            $cmdUtilOrOutput->writeln('As Ram type in Animal: '.$ramCount);
        }

        if($entityType != 'Neuter') {
            $cmdUtilOrOutput->writeln('As Neuter type in Animal: '.$neuterCount);
        }

        $cmdUtilOrOutput->writeln('-------------------------');
    }


    /**
     * @param Connection $conn
     * @param int $locationId
     * @return int
     */
    public static function deleteIncorrectNeutersFromRevokedBirths(Connection $conn, $locationId)
    {
        if(!is_int($locationId) || !ctype_digit($locationId)) { return null; }

        foreach (['animal_residence', 'result_table_breed_grades'] as $tableName) {
            $sql = "DELETE FROM $tableName
                WHERE animal_id IN (
                  SELECT id
                  FROM animal
                  WHERE CONCAT(uln_country_code, uln_number) IN (
                    SELECT CONCAT(uln_country_code, uln_number) FROM tag t
                    WHERE tag_status = 'UNASSIGNED' AND location_id = $locationId
                  ) AND location_id = $locationId
                        AND name ISNULL
                        AND pedigree_number ISNULL
                        AND type = 'Neuter'
                )";
            $recordsDeleted = SqlUtil::updateWithCount($conn, $sql);
        }

        $sql = "DELETE FROM animal
                WHERE CONCAT(uln_country_code, uln_number) IN (
                  SELECT CONCAT(uln_country_code, uln_number) FROM tag t
                  WHERE tag_status = 'UNASSIGNED' AND location_id = $locationId
                ) AND location_id = $locationId
                AND name ISNULL
                AND pedigree_number ISNULL
                AND type = 'Neuter'";
        $animalsDeleted = SqlUtil::updateWithCount($conn, $sql);

        return $animalsDeleted;
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|OutputInterface $output
     * @return int
     */
    public static function recursivelyFillMissingBreedCodesHavingBothParentBreedCodes(Connection $conn, $output)
    {
        $totalUpdateCount = 0;
        $iteration = 0;

        $output->writeln('Fixing breedCodes');
        
        do {
            $iterationUpdateCount = self::fillMissingBreedCodesHavingBothParentBreedCodes($conn);
            $totalUpdateCount += $iterationUpdateCount;

            if($iterationUpdateCount > 0 && $totalUpdateCount > 0) {
                $output->writeln('iteration ' . ++$iteration . ' updateCount: '. $iterationUpdateCount);
            }

        } while($iterationUpdateCount > 0);

        $prefix = $totalUpdateCount > 0 ? $totalUpdateCount : 'NO';
        $output->writeln('In total ' . $prefix . ' breedCodes were updated');

        return $totalUpdateCount;
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function fillMissingBreedCodesHavingBothParentBreedCodes(Connection $conn)
    {
        $sql = "UPDATE animal SET breed_code = v.new_breed_code
                FROM (
                  SELECT c.id as animal_id, c.breed_code as old_breed_code, mom.breed_code as new_breed_code
                  FROM animal c
                    INNER JOIN animal mom ON mom.id = c.parent_mother_id
                    INNER JOIN animal dad ON dad.id = c.parent_father_id
                  WHERE mom.breed_code = dad.breed_code AND c.breed_code ISNULL
                  UNION
                  SELECT c.id as animal_id, c.breed_code as old_breed_code, mom.breed_code as new_breed_code
                  FROM animal c
                    INNER JOIN animal mom ON mom.id = c.parent_mother_id
                    INNER JOIN animal dad ON dad.id = c.parent_father_id
                  WHERE mom.breed_code = dad.breed_code AND c.breed_code <> dad.breed_code
                        AND length(mom.breed_code) = 5 AND length(c.breed_code) > 5
                ) as v(animal_id, old_breed_code, new_breed_code)
                WHERE animal.id = v.animal_id";
        $sqlBatchUpdateCount = SqlUtil::updateWithCount($conn, $sql);

        $sql = "SELECT c.id as animal_id, mom.breed_code as mom_breed_code, dad.breed_code as dad_breed_code
                FROM animal c
                  INNER JOIN animal mom ON mom.id = c.parent_mother_id
                  INNER JOIN animal dad ON dad.id = c.parent_father_id
                WHERE mom.breed_code NOTNULL AND dad.breed_code NOTNULL AND c.breed_code ISNULL";
        $results = $conn->query($sql)->fetchAll();

        $updateString = '';
        $prefix = '';
        $toUpdateCount = 0;
        $loopCounter = 0;


        $nullResponse = null;
        foreach ($results as $result) {
            $animalId = $result['animal_id'];
            $motherBreedCodeString = $result['mom_breed_code'];
            $fatherBreedCodeString = $result['dad_breed_code'];
            $newChildBreedCode = BreedCodeUtil::calculateBreedCodeFromParentBreedCodes($fatherBreedCodeString, $motherBreedCodeString, $nullResponse);

            if($newChildBreedCode !== $nullResponse) {
                $updateString = $updateString . $prefix . "(" . $newChildBreedCode . "," . $animalId . ')';
                $prefix = ',';
                $toUpdateCount++;
            }
            $loopCounter++;
        }

        $stringFormattedBatchUpdateCount = 0;
        if($updateString !== '') {
            $sql = "UPDATE animal SET breed_code = c.new_breed_code
				FROM (VALUES " . $updateString . ") as c(new_breed_code, animal_id)
				WHERE c.animal_id = animal.id ";
            $stringFormattedBatchUpdateCount = SqlUtil::updateWithCount($conn, $sql);
        }

        return $sqlBatchUpdateCount + $stringFormattedBatchUpdateCount;
    }


    /**
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public static function removeAnimalsFromLocationAndAnimalResidence(Connection $conn, CommandUtil $cmdUtil)
    {
        $updateCount = 0;

        $csvOptions = (new CsvOptions())
            ->includeFirstLine()
            ->setInputFolder('app/Resources/imports/corrections/')
            ->setOutputFolder('app/Resources/output/corrections/')
            ->setFileName('remove_locations_by_uln.csv')
            ->setPipeSeparator()
            ;

        $csv = CsvParser::parse($csvOptions);

        $ulnCount = count($csv);
        if($ulnCount === 0) { return $updateCount; }

        $ulns = [];
        foreach ($csv as $records) {
            $ulnString = strtr($records[0], [' ' => '']);
            $ulnParts = Utils::getUlnFromString($ulnString);
            $ulns[$ulnString] = $ulnParts;
        }

        self::printAnimalsList($cmdUtil, $conn, $ulns);

        $continue = $cmdUtil->generateConfirmationQuestion('Continue with removing locations? (y/n, default = no)');

        if($continue) {
            $cmdUtil->setStartTimeAndPrintIt($ulnCount, 1);
            foreach ($ulns as $ulnString => $ulnParts) {
                if(self::removeAnimalFromLocationAndAnimalResidence($conn, $ulnString)) {
                    $updateCount++;
                }
                $cmdUtil->advanceProgressBar(1, 'locations of animals updated: '.$updateCount);
            }
            $cmdUtil->setEndTimeAndPrintFinalOverview();

            self::printAnimalsList($cmdUtil, $conn, $ulns);
        }

        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @param $ulnString
     * @return bool
     */
    private static function removeAnimalFromLocationAndAnimalResidence(Connection $conn, $ulnString)
    {
        $uln = Utils::getUlnFromString($ulnString);
        if($uln === null) { return false; }

        $ulnCountryCode = $uln[Constant::ULN_COUNTRY_CODE_NAMESPACE];
        $ulnNumber = $uln[Constant::ULN_NUMBER_NAMESPACE];

        $sql = "SELECT location_id FROM animal WHERE uln_country_code = '$ulnCountryCode' AND uln_number = '$ulnNumber'";
        $locationId = $conn->query($sql)->fetch()['location_id'];

        if($locationId !== null) {

            $sql = "SELECT r.id
                    FROM animal_residence r
                      INNER JOIN animal a ON a.id = r.animal_id
                    WHERE uln_country_code = '$ulnCountryCode' AND uln_number = '$ulnNumber'
                          AND r.location_id = $locationId";
            $animalResidences = $conn->query($sql)->fetchAll();

            //Always keep at least one animal residence on the location
            if(count($animalResidences) > 1) {
                $sql = "DELETE FROM animal_residence
                    WHERE id IN (
                      SELECT MAX(r.id) as id
                      FROM animal_residence r
                        INNER JOIN animal a ON a.id = r.animal_id
                      WHERE uln_country_code = '$ulnCountryCode' AND uln_number = '$ulnNumber'
                            AND r.location_id = $locationId AND end_date ISNULL
                      GROUP BY r.location_id, r.animal_id
                    )";
                $residenceDeleteCount = SqlUtil::updateWithCount($conn, $sql);
            }

            $sql = "UPDATE animal SET location_id = NULL
                WHERE uln_country_code = '$ulnCountryCode' AND uln_number = '$ulnNumber'
                AND location_id NOTNULL";
            $animalUpdateCount = SqlUtil::updateWithCount($conn, $sql);

            return $animalUpdateCount > 0;
        }

        return false;
    }


    /**
     * @param CommandUtil $cmdUtil
     * @param Connection $conn
     * @param array $ulns
     */
    private static function printAnimalsList(CommandUtil $cmdUtil, Connection $conn, array $ulns)
    {
        $ulnFilterString = SqlUtil::getUlnQueryFilter($ulns);

        $sql = "SELECT a.id as animal_id, CONCAT(uln_country_code, uln_number) as uln, a.location_id, ubn,
                        DATE(g.end_date) as depart_date, is_alive 
                FROM animal a
                 LEFT JOIN location l ON l.id = a.location_id
                 LEFT JOIN (
                  SELECT r.end_date, r.animal_id
                  FROM animal_residence r
                      INNER JOIN (
                                  SELECT MAX(r.end_date) as end_date, r.animal_id, r.location_id
                                  FROM animal_residence r
                                    INNER JOIN animal a ON a.id = r.animal_id 
                                  WHERE r.location_id = a.location_id AND $ulnFilterString
                                  GROUP BY animal_id, r.location_id
                      )gg ON gg.end_date = r.end_date AND r.animal_id = gg.animal_id AND r.location_id = gg.location_id
                  LIMIT 1
                 )g ON g.animal_id = a.id
                WHERE " . $ulnFilterString;

        $cmdUtil->writeln('___Animals in csv file___');
        foreach ($conn->query($sql)->fetchAll() as $animalRecords) {
            $animalId = $animalRecords['animal_id'];
            $uln = $animalRecords['uln'];
            $locationId = $animalRecords['location_id'];
            $ubn = $animalRecords['ubn'];
            $departDate = $animalRecords['depart_date'];
            $life = boolval($animalRecords['is_alive']) ? 'alive' : 'dead';
            $cmdUtil->writeln('animalId: ' . $animalId . '| uln: ' . $uln . ' |locationId: ' . $locationId
                .' |ubn : '.$ubn . '| residence end_date: ' . $departDate . '   '.$life);
        }

    }


    /**
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public static function killResurrectedDeadAnimalsAlreadyHavingFinishedLastDeclareLoss(Connection $conn, CommandUtil $cmdUtil)
    {
        $cmdUtil->writeln('Killing resurrected dead animals already having a FINISHED or FINISHED_WITH_WARNING last declare loss ... ');

        $sql = "UPDATE animal SET is_alive = FALSE WHERE id IN (
                  SELECT a.id as animal_id
                  --, a.is_alive, d.date_of_death, b.ubn,
                  --CONCAT(a.uln_country_code, a.uln_number) as uln,
                  --request_state, r.success_indicator, r.error_message
                  FROM animal a
                    INNER JOIN declare_loss d ON d.animal_id = a.id
                    INNER JOIN declare_base b ON b.id = d.id
                    INNER JOIN declare_base_response r ON r.request_id = b.request_id
                    INNER JOIN (
                                 SELECT d.animal_id, max(b.log_date) as last_log_date
                                 FROM declare_base b
                                   INNER JOIN declare_loss d ON d.id = b.id
                                 WHERE (b.request_state = '".RequestStateType::FINISHED."' 
                                     OR b.request_state = '".RequestStateType::FINISHED_WITH_WARNING."' 
                                     OR b.request_state = '".RequestStateType::REVOKED."'
                                     )
                                 GROUP BY d.animal_id
                               )last_declare ON last_declare.animal_id = d.animal_id AND last_declare.last_log_date = b.log_date
                  WHERE a.date_of_death NOTNULL AND is_alive AND request_state <> 'REVOKED'
                        AND a.date_of_death = d.date_of_death
                  ORDER BY a.date_of_death   
                )";
        $updateCount = SqlUtil::updateWithCount($conn, $sql);

        $countPrefix = $updateCount === 0 ? 'No' : $updateCount ;
        $cmdUtil->writeln($countPrefix.' animal is_alive states fixed');

        return $updateCount;
    }

}