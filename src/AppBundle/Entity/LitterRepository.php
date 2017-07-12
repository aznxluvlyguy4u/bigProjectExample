<?php

namespace AppBundle\Entity;
use AppBundle\Constant\JsonInputConstant;
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


    /**
     * @param int $animalId
     * @return int
     */
    public function getAggregatedLitterDataOfOffspring($animalId)
    { 
        if(!is_int($animalId)) {
            return [
                JsonInputConstant::LITTER_COUNT => null,
                JsonInputConstant::TOTAL_BORN_ALIVE_COUNT => null,
                JsonInputConstant::TOTAL_STILLBORN_COUNT => null,
                JsonInputConstant::EARLIEST_LITTER_DATE => null,
                JsonInputConstant::LATEST_LITTER_DATE => null
            ];
        }
        $sql = "SELECT COUNT(*) as litter_count, SUM(born_alive_count) as total_born_alive_count,
                    SUM(stillborn_count) as total_stillborn_count, MIN(litter_date) as earliest_litter_date,
                    MAX(litter_date) as latest_litter_date 
                FROM litter WHERE status <> 'REVOKED' AND (animal_mother_id = ".$animalId." OR animal_father_id = ".$animalId.")";
        $result = $this->getManager()->getConnection()->query($sql)->fetch();
        return $result == false ? null : $result;
    }


    /**
     * @param int $animalId
     * @return array|null
     */
    public function getLitterData($animalId)
    {
        if(!is_int($animalId)) { return null; }
        $sql = "SELECT (stillborn_count + l.born_alive_count) as size, 
                CONCAT(stillborn_count + l.born_alive_count, '-ling') as n_ling 
                FROM animal a
                  INNER JOIN litter l ON a.litter_id = l.id WHERE a.id = ".$animalId;
        $result = $this->getConnection()->query($sql)->fetch();
        return $result == false ? null : $result;
    }


    /**
     * @param $animalId
     * @return mixed
     */
    public function getLitterSize($animalId)
    {
        if(!is_int($animalId)) { return null; }
        $sql = "SELECT (stillborn_count + l.born_alive_count) as size
                FROM animal a
                  INNER JOIN litter l ON a.litter_id = l.id WHERE a.id = ".$animalId;
        $result = $this->getManager()->getConnection()->query($sql)->fetch();
        return $result == false ? null : $result['size'];
    }
}