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
    const UBN_PARAM = 'ubn';

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

    function clearMatchUpdatedAt(?string $ubn = null) {
        $clearAnimalSql = "UPDATE animal SET inbreeding_coefficient_match_updated_at = null WHERE inbreeding_coefficient_match_updated_at notnull";
        $clearLitterSql = "UPDATE litter SET inbreeding_coefficient_match_updated_at = null WHERE inbreeding_coefficient_match_updated_at notnull";
        if (!empty($ubn)) {
            $clearAnimalSql .= $this->historicAnimalFilter(self::ubnParameter());
            $clearLitterSql .= $this->litterFilterByLocation(self::ubnParameter());
        }
        $animalStatement = $this->getConnection()->prepare($clearAnimalSql);
        $litterStatement = $this->getConnection()->prepare($clearLitterSql);

        if (!empty($ubn)) {
            $animalStatement->bindParam(self::UBN_PARAM, $ubn);
            $litterStatement->bindParam(self::UBN_PARAM, $ubn);
        }

        $animalStatement->execute();
        $litterStatement->execute();
    }

    private static function ubnParameter(): string {
        return ':'.self::UBN_PARAM;
    }

    private function historicAnimalFilter(string $ubn, string $alias = 'animal'): string {
        return " AND $alias.id IN (SELECT
    animal_id
FROM animal_residence
    INNER JOIN location l on animal_residence.location_id = l.id
WHERE ubn = $ubn
GROUP BY animal_id

UNION DISTINCT

SELECT
    animal.id as animal_id
FROM animal
    INNER JOIN location l2 on animal.location_id = l2.id
WHERE ubn = $ubn)";
    }

    private function litterFilterByLocation(string $ubn, $alias = 'litter'): string {
        return " AND $alias.id IN (SELECT
    litter.id
FROM litter
         INNER JOIN declare_nsfo_base dnb on litter.id = dnb.id
WHERE dnb.ubn = $ubn)";
    }

    /**
     * @param int $limit
     * @param bool $recalculate
     * @param string|null $ubn
     * @return array
     */
    function findParentIdsPairsWithMissingInbreedingCoefficient(int $limit, bool $recalculate, ?string $ubn = null): array
    {
        $ramIdKey = ParentIdsPairUtil::RAM_ID;
        $eweIdKey = ParentIdsPairUtil::EWE_ID;

        $animalFilterPrefix = 'AND inbreeding_coefficient_id ISNULL';
        if ($recalculate) {
            $animalFilterPrefix = 'AND inbreeding_coefficient_match_updated_at ISNULL';
        }

        $ubnParameterValue = !empty($ubn) ? "'".$ubn."'" : null;
        if ($ubnParameterValue) {
            $animalFilterPrefix .= $this->historicAnimalFilter($ubnParameterValue);
        }

        $pairsFromAnimalSql = "SELECT
                                    parent_father_id as $ramIdKey,
                                    parent_mother_id as $eweIdKey
                                FROM animal
                                WHERE parent_mother_id NOTNULL AND parent_father_id NOTNULL
                                      $animalFilterPrefix
                                GROUP BY parent_father_id, parent_mother_id
                                LIMIT $limit";

        $pairs = $this->getConnection()->query($pairsFromAnimalSql)->fetchAll();

        if (empty($pairs)) {
            $litterFilterPrefix = 'AND inbreeding_coefficient_id ISNULL';
            if ($recalculate) {
                $litterFilterPrefix = 'AND inbreeding_coefficient_match_updated_at ISNULL';
            }

            if ($ubnParameterValue) {
                $litterFilterPrefix .= $this->litterFilterByLocation($ubnParameterValue);
            }

            $pairsFromLitterSql = "SELECT
                                    animal_father_id as $ramIdKey,
                                    animal_mother_id as $eweIdKey
                                FROM litter
                                WHERE animal_father_id NOTNULL AND animal_mother_id NOTNULL
                                      $litterFilterPrefix
                                GROUP BY animal_father_id, animal_mother_id
                                LIMIT $limit";

            $pairs = $this->getConnection()->query($pairsFromLitterSql)->fetchAll();
        }
        return ParentIdsPairUtil::fromSqlResult($pairs);
    }
}
