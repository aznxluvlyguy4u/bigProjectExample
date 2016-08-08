<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class LitterRepository
 * @package AppBundle\Entity
 */
class LitterRepository extends BaseRepository {

    /**
     * @param Ram|Ewe $animal
     * @return ArrayCollection
     */
    public function getLitters($animal)
    {
        if($animal instanceof Ewe) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('animalMother', $animal));
            return $this->getEntityManager()->getRepository(Litter::class)
                ->matching($criteria);

        } elseif ($animal instanceof Ram) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('animalFather', $animal));
            return $this->getEntityManager()->getRepository(Litter::class)
                ->matching($criteria);

        } else {
            return new ArrayCollection(); //empty ArrayCollection
        }
    }

}