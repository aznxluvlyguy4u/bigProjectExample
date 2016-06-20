<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;

/**
 * Class AnimalResidenceRepository
 * @package AppBundle\Entity
 */
class AnimalResidenceRepository extends BaseRepository {

    public function getLastByNullEndDate(Animal $animal)
    {
        $repository = $this->getEntityManager()->getRepository(Constant::ANIMAL_RESIDENCE_REPOSITORY);
        $results = $this->findBy(array('endDate' => null, 'animal_id' => $animal->getId()));

        if(sizeof($results) == 0) {
            return null;

        } else if(sizeof($results) == 1) {
            return $results[0];

        } else { //if(sizeof($results) > 1)
            return Utils::returnLastAnimalResidenceByStartDate($results);
        }
    }

}