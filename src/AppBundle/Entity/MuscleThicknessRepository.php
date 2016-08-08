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
    
}