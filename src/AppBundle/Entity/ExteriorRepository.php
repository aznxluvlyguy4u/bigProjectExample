<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class ExteriorRepository
 * @package AppBundle\Entity
 */
class ExteriorRepository extends BaseRepository {

    /**
     * If no Exterior is found a blank Exterior entity is returned
     * 
     * @param Animal $animal
     * @return Exterior
     */
    public function getLatestExterior(Animal $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);
        
        $latestExterior = $this->getEntityManager()->getRepository(Exterior::class)
            ->matching($criteria);

        if(sizeof($latestExterior) > 0) {
            $latestExterior = $latestExterior->get(0);
        } else { //create an empty default Exterior with default 0.0 values
            $latestExterior = new Exterior();
        }
        return $latestExterior;
    }


    /**
     * @param int $startYear
     * @param int $endYear
     * @return Collection
     */
    public function getExteriorsBetweenYears($startYear, $endYear)
    {
        $startDate = $startYear.'-01-01 00:00:00';
        $startTime = new \DateTime($startDate);

        $endYear = $endYear.'-12-31 23:59:59';
        $endTime = new \DateTime($endYear);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->gte('measurementDate', $startTime)) //greater or equal to this startTime
            ->andWhere(Criteria::expr()->lte('measurementDate', $endTime)) //less or equal to this endTime
            ->orderBy(['measurementDate' => Criteria::ASC])
        ;

        $measurements = $this->getEntityManager()->getRepository(Exterior::class)
            ->matching($criteria);

        return $measurements;
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
              SELECT MIN(measurement.id) as min_id
              FROM measurement INNER JOIN exterior x ON measurement.id = x.id 
              GROUP BY measurement_date, type, x.animal_id, x.kind, x.skull, x.muscularity, x.progress, x.proportion, x.exterior_type, x.leg_work, x.fur, x.general_appearence, x.height, x.breast_depth, x.torso_length, x.markings 
              HAVING COUNT(*) > 1";
            $results = $this->getEntityManager()->getConnection()->query($sql)->fetchAll();

            foreach ($results as $result) {
                $minId = $result['min_id'];
                $sql = "DELETE FROM exterior WHERE id = '".$minId."'";
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