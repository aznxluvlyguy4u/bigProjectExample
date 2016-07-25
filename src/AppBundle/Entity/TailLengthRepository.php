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

}