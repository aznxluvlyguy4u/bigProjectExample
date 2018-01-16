<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Criteria;

/**
 * Class BirthProgressRepository
 * @package AppBundle\Entity
 */
class BirthProgressRepository extends BaseRepository
{
    /**
     * @return array
     */
    public function getAllDescriptions()
    {
        $allBirthProgresses =  $this->findAll();
        $descriptions = [];
        /** @var BirthProgress $birthProgress */
        foreach ($allBirthProgresses as $birthProgress) {
            $description = $birthProgress->getDescription();
            $descriptions[$description] = $description;
        }

        return $descriptions;
    }

}