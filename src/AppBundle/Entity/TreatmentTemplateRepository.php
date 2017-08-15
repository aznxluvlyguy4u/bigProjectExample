<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\TreatmentTypeOption;

class TreatmentTemplateRepository extends BaseRepository
{

    /**
     * @param Location $location
     * @return array
     */
    public function findActiveIndividualTypeByLocation(Location $location)
    {
        return $this->findActiveByLocation($location, TreatmentTypeOption::INDIVIDUAL);
    }


    /**
     * @param Location $location
     * @return array
     */
    public function findActiveLocationTypeByLocation(Location $location)
    {
        return $this->findActiveByLocation($location, TreatmentTypeOption::LOCATION);
    }


    public function findActiveByLocation(Location $location, $type)
    {
        return $this->findBy(
            [
                'type' => $type,
                'isActive' => true,
                'location' => $location,
            ], [
            'logDate' => 'DESC'
        ]);
    }
}
