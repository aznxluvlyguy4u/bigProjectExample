<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class BodyFatRepository
 * @package AppBundle\Entity
 */
class BodyFatRepository extends BaseRepository {

    /**
     * @param Animal $animal
     * @return float
     */
    public function getLatestBodyFat(Animal $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        //BodyFat
        $latestBodyFat = $this->getEntityManager()->getRepository(BodyFat::class)
            ->matching($criteria);

        if(sizeof($latestBodyFat) > 0) {
            $latestBodyFat = $latestBodyFat->get(0);
            $latestBodyFat = $latestBodyFat->getFat();
        } else {
            $latestBodyFat = 0.00;
        }
        return $latestBodyFat;
    }
    
}