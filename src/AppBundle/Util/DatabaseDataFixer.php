<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
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
}