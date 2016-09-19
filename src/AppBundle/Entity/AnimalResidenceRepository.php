<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class AnimalResidenceRepository
 * @package AppBundle\Entity
 */
class AnimalResidenceRepository extends BaseRepository {

    public function getLastByNullEndDate(Animal $animal)
    {
        $repository = $this->getManager()->getRepository(Constant::ANIMAL_RESIDENCE_REPOSITORY);
        $results = $this->findBy(array('endDate' => null, 'animal_id' => $animal->getId()));

        if(sizeof($results) == 0) {
            return null;

        } else if(sizeof($results) == 1) {
            return $results[0];

        } else { //if(sizeof($results) > 1)
            return Utils::returnLastAnimalResidenceByStartDate($results);
        }
    }


    /**
     * @param Location $location
     * @param Animal $animal
     * @return AnimalResidence|null
     */
    public function getLastResidenceOnLocation($location, $animal)
    {
        if(! ($location instanceof Location && $animal instanceof Animal) ) {
            return null;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['startDate' => Criteria::DESC, 'logDate' => Criteria::DESC])
            ->setMaxResults(1);

        /** @var ArrayCollection $results */
        $results = $this->getManager()->getRepository(AnimalResidence::class)
            ->matching($criteria);

        if($results->count() > 0) {
            $lastResidence = $results->get(0);
        } else {
            $lastResidence = null;
        }

        return $lastResidence;
    }


    /**
     * @param Location $location
     * @param Animal $animal
     * @return AnimalResidence|null
     */
    public function getLastOpenResidenceOnLocation($location, $animal)
    {
        if(! ($location instanceof Location && $animal instanceof Animal) ) {
            return null;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('animal', $animal))
            ->andWhere(Criteria::expr()->isNull('endDate'))
            ->orderBy(['startDate' => Criteria::DESC, 'logDate' => Criteria::DESC])
            ->setMaxResults(1);

        /** @var ArrayCollection $results */
        $results = $this->getManager()->getRepository(AnimalResidence::class)
            ->matching($criteria);

        if($results->count() > 0) {
            $lastResidence = $results->get(0);
        } else {
            $lastResidence = null;
        }

        return $lastResidence;
    }
}