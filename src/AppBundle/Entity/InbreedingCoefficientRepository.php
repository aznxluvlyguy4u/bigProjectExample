<?php

namespace AppBundle\Entity;

use AppBundle\model\ParentIdsPair;
use AppBundle\Util\ParentIdsPairUtil;
use Doctrine\ORM\QueryBuilder;

/**
 * Class InbreedingCoefficientRepository
 * @package AppBundle\Entity
 */
class InbreedingCoefficientRepository extends BaseRepository {
    function exists(int $ramId, int $eweId): bool {
        $sql = "SELECT
                    COUNT(*) > 0 as exists
                FROM inbreeding_coefficient ic
                WHERE ram_id = :ramId AND ewe_id = :eweId";
        $statement = $this->getConnection()->prepare($sql);
        $statement->bindParam('ramId', $ramId);
        $statement->bindParam('eweId', $eweId);
        $statement->execute();

        return $statement->fetchColumn();
    }

    /**
     * @param ParentIdsPair $parentIdsPair
     * @return InbreedingCoefficient|null
     */
    function findByPair(ParentIdsPair $parentIdsPair): ?InbreedingCoefficient
    {
        return $this->findOneBy([
           'ram' => $parentIdsPair->getRamId(),
           'ewe' => $parentIdsPair->getEweId()
        ]);
    }

    /**
     * @param array|ParentIdsPair[] $parentIdsPairs
     * @return array|InbreedingCoefficient[]
     */
    function findByPairs(array $parentIdsPairs): array
    {
        if (empty($parentIdsPairs)) {
            return [];
        }

        $qb = $this->getManager()->createQueryBuilder();

        $qb
            ->select('i')
            ->from (InbreedingCoefficient::class, 'i');

        foreach ($parentIdsPairs as $parentIdsPair) {
            $qb->orWhere($qb->expr()->andX(
                $qb->expr()->andX(
                    $qb->expr()->eq('i.ram', $parentIdsPair->getRamId()),
                    $qb->expr()->eq('i.ewe', $parentIdsPair->getEweId())
                )
            ));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $limit
     * @return array
     */
    function findParentIdsPairsWithMissingInbreedingCoefficient(int $limit): array {
        $qb = $this->getManager()->createQueryBuilder();

        $qb
            ->select('a')
            ->from (Animal::class, 'a')
        ;
        $qb = $this->animalWhereBase($qb);

        $qb->setMaxResults($limit);

        $animals = $qb->getQuery()->getResult();

        if (empty($animals)) {
            $qb
                ->select('l')
                ->from (Litter::class, 'l')
            ;
            $qb = $this->litterWhereBase($qb);

            $qb->setMaxResults($limit);
            $litters = $qb->getQuery()->getResult();
            return ParentIdsPairUtil::fromLitters($litters);
        } else {
            return ParentIdsPairUtil::fromAnimals($animals);
        }
    }

    /**
     * @param int $limit
     * @param \DateTime|null $maxUpdateAt
     * @return array
     */
    function findParentIdsPairsBeforeMaxInbreedingCoefficientUpdatedAt(int $limit, ?\DateTime $maxUpdateAt = null): array {

        $qb = $this->getManager()->createQueryBuilder();

        $qb
            ->select('a')
            ->from (Animal::class, 'a')
        ;
        $qb = $this->animalWhereBase($qb);
        $qb->andWhere($qb->expr()->lt('a.inbreedingCoefficientMatchUpdatedAt', $maxUpdateAt));

        $qb->setMaxResults($limit);

        $animals = $qb->getQuery()->getResult();

        if (empty($animals)) {
            $qb
                ->select('l')
                ->from (Litter::class, 'l')
            ;
            $qb = $this->litterWhereBase($qb);
            $qb->andWhere($qb->expr()->lt('l.inbreedingCoefficientMatchUpdatedAt', $maxUpdateAt));

            $qb->setMaxResults($limit);
            $litters = $qb->getQuery()->getResult();
            return ParentIdsPairUtil::fromLitters($litters);
        } else {
            return ParentIdsPairUtil::fromAnimals($animals);
        }
    }

    private function litterWhereBase(QueryBuilder $qb): QueryBuilder {
        return $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->isNull('l.inbreedingCoefficient'),
                $qb->expr()->isNotNull('l.animalFather'),
                $qb->expr()->isNotNull('l.animalMother')
            )
        );
    }

    private function animalWhereBase(QueryBuilder $qb): QueryBuilder {
        return $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->isNull('a.inbreedingCoefficient'),
                $qb->expr()->isNotNull('a.parentFather'),
                $qb->expr()->isNotNull('a.parentMother')
            )
        );
    }
}