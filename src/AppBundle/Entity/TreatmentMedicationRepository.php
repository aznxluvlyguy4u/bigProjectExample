<?php

namespace AppBundle\Entity;

use Exception;

class TreatmentMedicationRepository extends BaseRepository
{
    /**
     * @param bool $activeOnly
     * @param string $type
     * @return array
     * @throws Exception
     */
    public function findByQueries($activeOnly = false, $type = null)
    {
        $criteria = [];

        if ($activeOnly) { $criteria['isActive'] = true; }

        return $this->findBy($criteria,  ['name' => 'ASC']);
    }
}
