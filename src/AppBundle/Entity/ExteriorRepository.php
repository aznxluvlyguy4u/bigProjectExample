<?php

namespace AppBundle\Entity;
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
    
}