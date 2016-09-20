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
        $latestTailLength = $this->getManager()->getRepository(TailLength::class)
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


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContradictingTailLengthsForExportFile()
    {
        $sql = "
             SELECT i.last_name as inspector, n.id as metingId, a.id as animal_id, DATE(n.measurement_date) as meetdatum, z.length as staartlengte, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, DATE(a.date_of_birth) as geboortedatum FROM measurement n
              INNER JOIN (
                           SELECT m.animal_id_and_date
                           FROM measurement m
                             INNER JOIN tail_length x ON m.id = x.id
                           GROUP BY m.animal_id_and_date
                           HAVING (COUNT(*) > 1)
                         ) t on t.animal_id_and_date = n.animal_id_and_date
              INNER JOIN tail_length z ON z.id = n.id
              LEFT JOIN person i ON i.id = n.inspector_id
              LEFT JOIN animal a ON a.id = z.animal_id";
        return  $this->getEntityManager()->getConnection()->query($sql)->fetchAll();
    }
}