<?php

namespace AppBundle\Output;

use AppBundle\Component\Utils;
use AppBundle\Entity\Animal;
use Doctrine\Common\Collections\Collection;

/**
 * Class WeightMeasurementsOutput
 * @package AppBundle\Output
 */
class WeightMeasurementsOutput
{
    /**
     * @param array $animals
     * @return array
     */
    public static function createForLiveStock($animals)
    {
        $weightMeasurements = array();

        foreach($animals as $animal)
        {
            $weightMeasurements[] = self::createForAnimal($animal);
        }

        return $weightMeasurements;
    }

    /**
     * @param Animal $animal
     * @return array
     */
    public static function createForAnimal(Animal $animal)
    {
        $lastWeightMeasurement = Utils::returnLastWeightMeasurement($animal->getWeightMeasurements());

        if($lastWeightMeasurement == null){
            $weight = '';
            $weightMeasurementDate = '';
        } else {
            $weight = $lastWeightMeasurement->getWeight();
            $weightMeasurementDate = $lastWeightMeasurement->getWeightMeasurementDate();
        }

        $result = array(
            'uln_country_code' => $animal->getUlnCountryCode(),
            'uln_number' => $animal->getUlnNumber(),
            'weight' => $weight,
            'weight_measurement_date' => $weightMeasurementDate
        );

        return $result;
    }
}