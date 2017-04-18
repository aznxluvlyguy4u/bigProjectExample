<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class DatabaseDataFixer
{

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
            $sql = "ALTER TABLE ".$tableName." ALTER id SET DEFAULT nextval('".$tableName."_id_seq')";

            if($cmdUtil) { $cmdUtil->writeln($tableName.' maxId sequence = '.$newMaxIdInSequence); }
        }
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|null $cmdUtil
     */
    public static function fillMissingAnimalOrderNumbers(Connection $conn, CommandUtil $cmdUtil = null)
    {
        $sql = "SELECT id, uln_number
                FROM animal WHERE uln_number NOTNULL
                    AND (animal.animal_order_number = '' OR animal.animal_order_number ISNULL)";
        $results = $conn->query($sql)->fetchAll();

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(count($results)+1, 1); }
        
        foreach ($results as $result) {
            self::saveLast5UlnCharsAsAnimalOrderNumber($conn, $result['id'], $result['uln_number']);
            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1); }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|null $cmdUtil
     */
    public static function fixIncongruentAnimalOrderNumbers(Connection $conn, CommandUtil $cmdUtil = null)
    {
        $sql = "SELECT id, uln_number, animal_order_number FROM animal";
        $results = $conn->query($sql)->fetchAll();

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(count($results)+1, 1); }

        foreach ($results as $result) {
            $id = $result['id'];
            $uln = $result['uln_number'];
            $animalOrderNumber = $result['animal_order_number'];
            $ulnMin = StringUtil::getUlnWithoutOrderNumber($uln, $animalOrderNumber);

            if($ulnMin.$animalOrderNumber != $uln) {
                self::saveLast5UlnCharsAsAnimalOrderNumber($conn, $id, $uln);
                if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1); }
            }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }


    /**
     * @param Connection $conn
     * @param int $animalId
     * @param string $uln
     */
    private static function saveLast5UlnCharsAsAnimalOrderNumber(Connection $conn, $animalId, $uln) {
        $newAnimalOrderNumber = StringUtil::getLast5CharactersFromString($uln);
        $sql = "UPDATE animal SET animal_order_number = '".$newAnimalOrderNumber."'
                WHERE id = ".$animalId;
        $conn->exec($sql);
    }
}