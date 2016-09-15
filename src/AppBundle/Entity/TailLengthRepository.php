<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class TailLengthRepository
 * @package AppBundle\Entity
 */
class TailLengthRepository extends BaseRepository {

    /**
     * @param Animal $animal
     * @return float
     */
    public function getLatestTailLength(Animal $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        //TailLength
        $latestTailLength = $this->getEntityManager()->getRepository(TailLength::class)
            ->matching($criteria);

        if(sizeof($latestTailLength) > 0) {
            $latestTailLength = $latestTailLength->get(0);
            $latestTailLength = $latestTailLength->getLength();
        } else {
            $latestTailLength = 0.00;
        }
        return $latestTailLength;
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteDuplicates()
    {
        $em = $this->getEntityManager();

        $count = 0;

        $hasDuplicates = true;
        while($hasDuplicates) {
            $sql = "
              SELECT MIN(measurement.id) as min_id, COUNT(*), measurement_date, animal_id, length
              FROM measurement INNER JOIN tail_length x ON measurement.id = x.id
              GROUP BY measurement_date, type, x.animal_id, x.length
              HAVING COUNT(*) > 1";
            $results = $this->getEntityManager()->getConnection()->query($sql)->fetchAll();

            foreach ($results as $result) {
                $minId = $result['min_id'];
                $sql = "DELETE FROM tail_length WHERE id = '".$minId."'";
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = '".$minId."'";
                $em->getConnection()->exec($sql);
                $count++;
            }
            if(count($results) == 0) { $hasDuplicates = false; }
        }
        return $count;
    }
}