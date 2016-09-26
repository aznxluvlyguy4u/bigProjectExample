<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

class MeasurementRepository extends BaseRepository {

    /**
     * @param int $startYear
     * @param int $endYear
     * @return Collection
     */
    public function getMeasurementsBetweenYears($startYear, $endYear)
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

        $measurements = $this->getManager()->getRepository(Measurement::class)
        ->matching($criteria);

        return $measurements;
    }


    /**
     * @param array $results
     * @return array
     */
    protected function groupSqlMeasurementResultsByAnimalIdAndDate($results)
    {
        $measurementsGroupedByAnimalAndDate = array();
        foreach ($results as $result) {
            $animalIdAndData = $result['animal_id_and_date'];
            if(array_key_exists($animalIdAndData, $measurementsGroupedByAnimalAndDate)) {
                $items = $measurementsGroupedByAnimalAndDate[$animalIdAndData];
                $items->add($result);
                $measurementsGroupedByAnimalAndDate[$animalIdAndData] = $items;
            } else {
                //First entry
                $items = new ArrayCollection();
                $items->add($result);
                $measurementsGroupedByAnimalAndDate[$animalIdAndData] = $items;
            }
        }
        return $measurementsGroupedByAnimalAndDate;
    }
    
}