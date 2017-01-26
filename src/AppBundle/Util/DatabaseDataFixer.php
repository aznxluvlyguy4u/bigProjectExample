<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseDataFixer
{

    /**
     * @param ObjectManager $em
     * @param CommandUtil|null $cmdUtil
     */
    public static function fillMissingAnimalOrderNumbers(ObjectManager $em, CommandUtil $cmdUtil = null)
    {
        $sql = "SELECT id, uln_number
                FROM animal WHERE uln_number NOTNULL
                    AND (animal.animal_order_number = '' OR animal.animal_order_number ISNULL)";
        $results = $em->getConnection()->query($sql)->fetchAll();

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(count($results)+1, 1); }
        
        foreach ($results as $result) {
            self::saveLast5UlnCharsAsAnimalOrderNumber($em, $result['id'], $result['uln_number']);
            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1); }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|OutputInterface $cmdUtilOrOutputInterface
     */
    public static function fixIncongruentAnimalOrderNumbers(Connection $conn, $cmdUtilOrOutputInterface = null)
    {
        $sql = "WITH rows AS (
                  UPDATE animal SET animal_order_number = SUBSTRING(uln_number, LENGTH(uln_number) - 4)
                    WHERE animal_order_number <> SUBSTRING(uln_number, LENGTH(uln_number) - 4)
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows";
        $count = $conn->query($sql)->fetch()['count'];

        if($cmdUtilOrOutputInterface != null) {
            $message = $count == 0 ? 'All animalOrderNumbers were already correct!' : $count . ' animalOrderNumbers fixed!';
            $cmdUtilOrOutputInterface->writeln($message);
        }
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @param string $uln
     */
    private static function saveLast5UlnCharsAsAnimalOrderNumber(ObjectManager $em, $animalId, $uln) {
        $newAnimalOrderNumber = StringUtil::getLast5CharactersFromString($uln);
        $sql = "UPDATE animal SET animal_order_number = '".$newAnimalOrderNumber."'
                WHERE id = ".$animalId;
        $em->getConnection()->exec($sql);
    }
}