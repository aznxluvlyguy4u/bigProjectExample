<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class MuscleThicknessRepository
 * @package AppBundle\Entity
 */
class MuscleThicknessRepository extends BaseRepository {

    /**
     * @param Animal $animal
     * @return float
     */
    public function getLatestMuscleThickness(Animal $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        //MuscleThickness
        $latestMuscleThickness = $this->getEntityManager()->getRepository(MuscleThickness::class)
            ->matching($criteria);

        if(sizeof($latestMuscleThickness) > 0) {
            $latestMuscleThickness = $latestMuscleThickness->get(0);
            $latestMuscleThickness = $latestMuscleThickness->getMuscleThickness();
        } else {
            $latestMuscleThickness = 0.0;
        }

        return $latestMuscleThickness;
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
              SELECT MIN(measurement.id) as min_id, COUNT(*), measurement_date, animal_id, muscle_thickness
              FROM measurement INNER JOIN muscle_thickness x ON measurement.id = x.id
              GROUP BY measurement_date, type, x.animal_id, x.muscle_thickness
              HAVING COUNT(*) > 1";
            $results = $this->getEntityManager()->getConnection()->query($sql)->fetchAll();

            foreach ($results as $result) {
                $minId = $result['min_id'];
                $sql = "DELETE FROM muscle_thickness WHERE id = '".$minId."'";
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