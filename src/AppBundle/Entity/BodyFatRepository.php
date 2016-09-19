<?php

namespace AppBundle\Entity;
use AppBundle\Constant\MeasurementConstant;
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
        $latestBodyFat = $this->getManager()->getRepository(BodyFat::class)
            ->matching($criteria);

        if(sizeof($latestBodyFat) > 0) {
            $latestBodyFat = $latestBodyFat->get(0);
            $measurementDate = $latestBodyFat->getMeasurementDate();
            $fatOne = $latestBodyFat->getFat1()->getFat();
            $fatTwo = $latestBodyFat->getFat2()->getFat();
            $fatThree = $latestBodyFat->getFat3()->getFat();

            $bodyFat[MeasurementConstant::DATE] = $measurementDate;
            $bodyFat[MeasurementConstant::ONE] = $fatOne;
            $bodyFat[MeasurementConstant::TWO] = $fatTwo;
            $bodyFat[MeasurementConstant::THREE] = $fatThree;
        } else {
            $bodyFat[MeasurementConstant::DATE] = '';
            $bodyFat[MeasurementConstant::ONE] = 0.00;
            $bodyFat[MeasurementConstant::TWO] = 0.00;
            $bodyFat[MeasurementConstant::THREE] = 0.00;
        }
        return $bodyFat;
    }


    /**
     * @param Animal $animal
     * @return string
     */
    public function getLatestBodyFatAsString(Animal $animal)
    {
        $bodyFats = $this->getLatestBodyFat($animal);

        $fat1 = $bodyFats[MeasurementConstant::ONE];
        $fat2 = $bodyFats[MeasurementConstant::TWO];
        $fat3 = $bodyFats[MeasurementConstant::THREE];
        
        if($fat1 == 0 && $fat2 == 0 && $fat3 == 0) {
            return '';
        } else {
            return $fat1.'/'.$fat2.'/'.$fat3;
        }
    }
}