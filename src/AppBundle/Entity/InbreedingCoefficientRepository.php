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

    function clearMatchUpdatedAt() {
        $clearAnimalSql = "UPDATE animal SET inbreeding_coefficient_match_updated_at = null WHERE inbreeding_coefficient_match_updated_at notnull";
        $clearLitterSql = "UPDATE litter SET inbreeding_coefficient_match_updated_at = null WHERE inbreeding_coefficient_match_updated_at notnull";

        $this->getConnection()->query($clearAnimalSql)->execute();
        $this->getConnection()->query($clearLitterSql)->execute();
    }

    /**
     * @param int $limit
     * @param bool $recalculate
     * @return array
     */
    function findParentIdsPairsWithMissingInbreedingCoefficient(int $limit, bool $recalculate): array
    {
        $ramIdKey = ParentIdsPairUtil::RAM_ID;
        $eweIdKey = ParentIdsPairUtil::EWE_ID;

        $filterPrefix = 'AND inbreeding_coefficient_id ISNULL';
        if ($recalculate) {
            $filterPrefix = 'AND inbreeding_coefficient_match_updated_at ISNULL';
        }

        $pairsFromAnimalSql = "SELECT
                                    parent_father_id as $ramIdKey,
                                    parent_mother_id as $eweIdKey
                                FROM animal
                                WHERE parent_mother_id NOTNULL AND parent_father_id NOTNULL
                                      $filterPrefix
                                GROUP BY parent_father_id, parent_mother_id
                                LIMIT $limit";

        $pairs = $this->getConnection()->query($pairsFromAnimalSql)->fetchAll();

        if (empty($pairs)) {
            $pairsFromAnimalSql = "SELECT
                                    animal_father_id as $ramIdKey,
                                    animal_mother_id as $eweIdKey
                                FROM litter
                                WHERE animal_father_id NOTNULL AND animal_mother_id NOTNULL
                                      AND $filterPrefix ISNULL
                                GROUP BY animal_father_id, animal_mother_id
                                LIMIT $limit";

            $pairs = $this->getConnection()->query($pairsFromAnimalSql)->fetchAll();
        }
        return ParentIdsPairUtil::fromSqlResult($pairs);
    }
}
