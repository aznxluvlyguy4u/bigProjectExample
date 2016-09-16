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
    
}