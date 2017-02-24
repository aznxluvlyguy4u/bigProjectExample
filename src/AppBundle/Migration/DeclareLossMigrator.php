<?php


namespace AppBundle\Migration;


use AppBundle\Util\CommandUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class DeclareLossMigrator
{
    const UPDATE_BATCH_SIZE = 10000;


    /**
     * @param ObjectManager $em
     * @param CommandUtil|null $cmdUtil
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function fixAnimalDateOfDeathAndAliveStateByDeclareLossStatus(ObjectManager $em, CommandUtil $cmdUtil = null)
    {
        /** @var Connection $conn */
        $conn = $em->getConnection();
        
        //First check if animals that are mismatched with declareLosses have no revoked declareLosses
        $sql = "SELECT COUNT(*) FROM declare_loss ll
                  INNER JOIN declare_base bb ON ll.id = bb.id
                  INNER JOIN (
                    SELECT DISTINCT (animal_id) as animal_id FROM animal a
                      INNER JOIN declare_loss d ON d.animal_id = a.id
                      INNER JOIN declare_base b ON b.id = d.id
                    WHERE b.request_state = 'FINISHED' AND (a.date_of_death ISNULL or a.is_alive = TRUE)
                    )z ON z.animal_id = ll.animal_id
                WHERE bb.request_state = 'REVOKED'";
        $possibleRevokedLossesCount = $conn->query($sql)->fetch()['count'];
        if($possibleRevokedLossesCount > 0) {
            //implement further checks before editing the animalData
            return false;
        }
        
        $sql = "SELECT animal_id, d.date_of_death FROM animal a
                    INNER JOIN declare_loss d ON d.animal_id = a.id
                    INNER JOIN declare_base b ON b.id = d.id
                WHERE b.request_state = 'FINISHED' AND a.date_of_death ISNULL";
        $results = $conn->query($sql)->fetchAll();

        $totalCount = count($results);
        if($totalCount == 0) {
            return true;
        }

        $updateString = '';
        $toUpdateCount = 0;
        $UpdatedCount = 0;
        $loopCounter = 0;

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($totalCount, 1); }

        foreach ($results as $result) {
            $animalId = intval($result['animal_id']);
            $dateOfDeath = $result['date_of_death'];

            $updateString = $updateString . "('" . $dateOfDeath . "'," . $animalId . '),';
            $toUpdateCount++;
            $loopCounter++;

            //Update fathers
            if (($totalCount == $loopCounter || ($toUpdateCount % self::UPDATE_BATCH_SIZE == 0 && $toUpdateCount != 0))
                && $updateString != ''
            ) {
                $updateString = rtrim($updateString, ',');
                $sql = "UPDATE animal as a SET date_of_death = CAST(c.date_of_death AS DATE), is_alive = FALSE
				FROM (VALUES " . $updateString . ") as c(date_of_death, animal_id) WHERE c.animal_id = a.id ";
                $conn->exec($sql);
                //Reset batch values
                $updateString = '';
                $UpdatedCount += $toUpdateCount;
                $toUpdateCount = 0;
            }
            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'AnimalDeathData updated|inBatch: '.$UpdatedCount.'|'.$toUpdateCount); }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
        return true;
        
    }

}