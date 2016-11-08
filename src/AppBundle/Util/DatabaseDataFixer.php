<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;

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
     * @param ObjectManager $em
     * @param CommandUtil|null $cmdUtil
     */
    public static function fixIncongruentAnimalOrderNumbers(ObjectManager $em, CommandUtil $cmdUtil = null)
    {
        $sql = "SELECT id, uln_number, animal_order_number FROM animal";
        $results = $em->getConnection()->query($sql)->fetchAll();

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(count($results)+1, 1); }

        foreach ($results as $result) {
            $id = $result['id'];
            $uln = $result['uln_number'];
            $animalOrderNumber = $result['animal_order_number'];
            $ulnMin = StringUtil::getUlnWithoutOrderNumber($uln, $animalOrderNumber);

            if($ulnMin.$animalOrderNumber != $uln) {
                self::saveLast5UlnCharsAsAnimalOrderNumber($em, $id, $uln);
                if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1); }
            }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
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