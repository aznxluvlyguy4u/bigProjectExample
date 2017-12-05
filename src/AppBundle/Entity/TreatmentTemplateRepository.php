<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\TreatmentTypeOption;

class TreatmentTemplateRepository extends BaseRepository
{

    /**
     * @param Location $location
     * @param boolean $activeOnly
     * @return array
     */
    public function findIndividualTypeByLocation($location, $activeOnly)
    {
        return $this->findActiveByLocation($location, TreatmentTypeOption::INDIVIDUAL, $activeOnly);
    }


    /**
     * @param Location $location
     * @param boolean $activeOnly
     * @return array
     */
    public function findLocationTypeByLocation($location, $activeOnly)
    {
        return $this->findActiveByLocation($location, TreatmentTypeOption::LOCATION, $activeOnly);
    }


    public function findActiveByLocation($location, $type, $activeOnly)
    {
        $filter =
            [
                'type' => $type,
                'location' => $location,
            ];

        if ($activeOnly) {
            $filter['isActive'] = true;
        }

        return $this->findBy($filter, ['logDate' => 'DESC']);
    }
}
