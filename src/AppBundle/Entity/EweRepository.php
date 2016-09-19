<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Class EweRepository
 * @package AppBundle\Entity
 */
class EweRepository extends AnimalRepository {

    /**
     * @param Ewe $mother
     * @param boolean $isPersist
     * @param boolean $isFlush
     */
    public function generateLitterIds($mother, $isPersist = true, $isFlush = true)
    {
        /** @var ObjectManager $em */
        $em = $this->getManager();

        if($mother instanceof Ewe) {
            $uln = $mother->getUln();

            $litters = $mother->getLitters();
            $litterCount = $litters->count();

            for($i = 0; $i < $litterCount; $i++) {
                /** @var Litter $litter */
                $litter = $litters->get($i);
                $litterNumber = str_pad($i+1, 2, "0", STR_PAD_LEFT);
                $litter->setLitterGroup($uln.'_'.$litterNumber);

                if($isPersist) { $em->persist($litter); }
            }
            if($isPersist) { $em->persist($mother); }
            if($isFlush) { $em->flush(); }
        }
    }


    /**
     * @param int $startId
     * @param int $endId
     * @return Collection
     */
    public function getEwesById($startId, $endId)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->gte('id', $startId))
            ->andWhere(Criteria::expr()->lte('id', $endId))
            ->orderBy(['id' => Criteria::ASC])
        ;

        return $this->getManager()->getRepository(Ewe::class)
            ->matching($criteria);
    }

    /**
     * @return int|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMaxEweId()
    {
        $sql = "SELECT MAX(id) FROM ewe";
        return $this->executeSqlQuery($sql);
    }
}