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
     * @return string
     */
    public function getLatestBodyFat(Animal $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        /**
         * @var BodyFat $latestBodyFat
         */
        $latestBodyFat = $this->getEntityManager()->getRepository(BodyFat::class)
            ->matching($criteria);

        if(sizeof($latestBodyFat) > 0) {
            $latestBodyFat = $latestBodyFat->get(0);
            $fatOne = $latestBodyFat->getFat1()->getFat();
            $fatTwo = $latestBodyFat->getFat2()->getFat();
            $fatThree = $latestBodyFat->getFat3()->getFat();

            $latestBodyFat = $fatOne ." / ". $fatTwo ." / ". $fatThree ;
        } else {
            $latestBodyFat = '-/-/-';
        }
        return $latestBodyFat;
    }
    
}