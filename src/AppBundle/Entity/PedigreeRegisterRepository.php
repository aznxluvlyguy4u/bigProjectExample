<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class PedigreeRegisterRepository
 * @package AppBundle\Entity
 */
class PedigreeRegisterRepository extends BaseRepository {

    /**
     * @param $animalId
     * @param bool $onlyIncludeIfOfficiallyRecognized
     * @return PedigreeRegister
     */
    public function getByAnimalId($animalId, $onlyIncludeIfOfficiallyRecognized = true)
    {
        if(!is_int($animalId)) { return null; }

        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('a', 'pr')
            ->from(Animal::class, 'a', 'a.id')
            ->innerJoin('a.pedigreeRegister', 'pr', Join::WITH, $qb->expr()->eq('a.pedigreeRegister', 'pr.id'))
            ->where($qb->expr()->eq('a.id', $animalId))
            ->getFirstResult()
        ;

        if ($onlyIncludeIfOfficiallyRecognized) {
            $qb->andWhere($qb->expr()->eq('pr.isOfficiallyRecognized', 'true'));
        }

        /** @var Animal[] $animals */
        $animals = $qb->getQuery()->getResult();
        return $animals ? array_pop($animals)->getPedigreeRegister(): null;
    }


    /**
     * @return array
     */
    public function getNsfoRegisters()
    {
        return $this->findBy(['isRegisteredWithNsfo' => true], ['abbreviation' => 'ASC']);
    }


    /**
     * @param $breederNumber
     * @return PedigreeRegister|null
     */
    public function findOneByBreederNumber($breederNumber)
    {
        $qb = $this->getManager()->createQueryBuilder();

        $qb
            ->select('rr', 'r')
            ->from(PedigreeRegisterRegistration::class, 'rr')
            ->innerJoin('rr.pedigreeRegister', 'r', Join::WITH, $qb->expr()->eq('rr.pedigreeRegister', 'r.id'))
            ->where(
                $qb->expr()->eq('rr.breederNumber', "'".$breederNumber."'")
            )
            ->orderBy('rr.isActive', Criteria::DESC)
            ->setMaxResults(1)
        ;

        $pedigreeRegisterRegistration = $this->returnFirstQueryResult($qb->getQuery()->getResult());
        if($pedigreeRegisterRegistration instanceof PedigreeRegisterRegistration) {
            return $pedigreeRegisterRegistration->getPedigreeRegister();
        }

        return null;
    }


    /**
     * @param string|null $abbreviation
     * @return PedigreeRegister|null
     */
    public function findOneByAbbreviation($abbreviation): ?PedigreeRegister
    {
        if (empty($abbreviation) || !is_string($abbreviation)) {
            return null;
        }

        $abbreviation = strtoupper($abbreviation);

        $qb = $this->getManager()->createQueryBuilder();
        $query =
            $qb
                ->select('r')
                ->from(PedigreeRegister::class, 'r')
                ->where("UPPER(r.abbreviation) = '$abbreviation'")
                ->setMaxResults(1)
                ->getQuery();

        $result = $query->getResult();
        return empty($result) ? null : reset($result);
    }
}