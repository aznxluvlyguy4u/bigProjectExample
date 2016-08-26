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
     * @return array
     */
    public function getLatestBodyFat(Animal $animal)
    {
        $bodyFat = array();

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
            $measurementDate = $latestBodyFat->getMeasurementDate();
            $fatOne = $latestBodyFat->getFat1()->getFat();
            $fatTwo = $latestBodyFat->getFat2()->getFat();
            $fatThree = $latestBodyFat->getFat3()->getFat();

            $bodyFat['date'] = $measurementDate;
            $bodyFat['one'] = $fatOne;
            $bodyFat['two'] = $fatTwo;
            $bodyFat['three'] = $fatThree;
        } else {
            $bodyFat['date'] = '';
            $bodyFat[0] = 0.00;
            $bodyFat[1] = 0.00;
            $bodyFat[2] = 0.00;
        }
        return $bodyFat;
    }
    
}