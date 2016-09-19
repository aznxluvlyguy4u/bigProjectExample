<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Validator\Constraints\Collection;

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
            return $this->getManager()->getRepository(Litter::class)
                ->matching($criteria);

        } elseif ($animal instanceof Ram) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('animalFather', $animal));
            return $this->getManager()->getRepository(Litter::class)
                ->matching($criteria);

        } else {
            return new ArrayCollection(); //empty ArrayCollection
        }
    }


    /**
     * @param int $startId
     * @param int $endId
     * @return Collection
     */
    public function getLittersById($startId, $endId)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->gte('id', $startId))
            ->andWhere(Criteria::expr()->lte('id', $endId))
            ->orderBy(['id' => Criteria::ASC])
        ;

        return $this->getManager()->getRepository(Litter::class)
            ->matching($criteria);
    }
    
    
    /**
     * @return int|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMaxLitterId()
    {
        $sql = "SELECT MAX(id) FROM litter";
        return $this->executeSqlQuery($sql);
    }


    /**
     * @param int $litterId
     * @param bool $isAlive
     * @return int
     */
    public function getChildrenByAliveState($litterId, $isAlive = true)
    {
        $sql = "SELECT COUNT(animal.id) FROM animal INNER JOIN litter ON animal.litter_id = litter.id WHERE animal.is_alive = '".$isAlive."' AND animal.litter_id = '".$litterId."'";
        return $this->getManager()->getConnection()->query($sql)->fetch()['count'];
    }
}