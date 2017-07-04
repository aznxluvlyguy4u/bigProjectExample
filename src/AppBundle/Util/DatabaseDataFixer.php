<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

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
                dump($newChildBreedCode . ' = '.$fatherBreedCodeString.' + '.$motherBreedCodeString);
                $updateString = $updateString . $prefix . "('" . $newChildBreedCode . "'," . $animalId . ')';
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
}