<?php

namespace AppBundle\Entity;

use AppBundle\model\ParentIdsPair;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\ParentIdsPairUtil;
use AppBundle\Util\SqlUtil;
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
     * @param  int  $ramId
     * @param  int  $eweId
     * @return InbreedingCoefficient|null
     */
    function findByParentIds(int $ramId, int $eweId): ?InbreedingCoefficient
    {
        return $this->findOneBy([
            'ram' => $ramId,
            'ewe' => $eweId
        ]);
    }

    /**
     * @param ParentIdsPair $parentIdsPair
     * @return InbreedingCoefficient|null
     */
    function findByPair(ParentIdsPair $parentIdsPair): ?InbreedingCoefficient
    {
        return $this->findByParentIds($parentIdsPair->getRamId(),$parentIdsPair->getEweId());
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

        $limitFilter = $limit === 0 ? '' : "LIMIT $limit";

        $pairsFromAnimalSql = "SELECT
                                    parent_father_id as $ramIdKey,
                                    parent_mother_id as $eweIdKey,
                                    sum(location_id)
                                FROM animal
                                WHERE parent_mother_id NOTNULL AND parent_father_id NOTNULL
                                      $animalFilterPrefix
                                GROUP BY parent_father_id, parent_mother_id
                                ORDER BY sum(location_id)
                                $limitFilter";

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
                                $limitFilter";

            $pairs = $this->getConnection()->query($pairsFromLitterSql)->fetchAll();
        }
        return ParentIdsPairUtil::fromSqlResult($pairs);
    }


    public function matchAnimalsAndLitters($animalIds = [], $litterIds = []) {

        if (empty($animalIds) && empty($litterIds)) {
            return [];
        }

        $matchAnimalCount = 0;
        $matchLitterCount = 0;

        if (!empty($animalIds)) {
            $animalIdsString = SqlUtil::getFilterListString($animalIds, false);
            $animalFilter = " AND id IN ($animalIdsString)";


            $animalSelectSql = "SELECT
                             animal.id as animal_id,
                             ic.id as inbreeding_coefficient_id
                         FROM inbreeding_coefficient ic
                         INNER JOIN (
                             SELECT
                                 id,
                                 parent_mother_id,
                                 parent_father_id
                             FROM animal
                             WHERE parent_father_id NOTNULL AND parent_mother_id NOTNULL
                                      $animalFilter
                         )animal ON animal.parent_father_id = ic.ram_id AND animal.parent_mother_id = ic.ewe_id";
            $animalSelectResult = $this->getConnection()->query($animalSelectSql)->fetchAll();

            $animalUpdatedCount = $this->updateAnimalBySelectResult($animalSelectResult);
            $matchAnimalCount += $animalUpdatedCount;
        }

        if (!empty($litterIds)) {
            $litterIdsString = SqlUtil::getFilterListString($litterIds, false);
            $litterFilter = " AND id IN ($litterIdsString)";

            $litterSelectSql = "SELECT
                                 litter.id as litter_id,
                                 ic.id as inbreeding_coefficient_id
                             FROM inbreeding_coefficient ic
                             INNER JOIN (
                                 SELECT
                                     id,
                                     animal_father_id,
                                     animal_mother_id
                                 FROM litter
                                 WHERE animal_father_id NOTNULL AND animal_mother_id NOTNULL
                                          $litterFilter
                             )litter ON litter.animal_father_id = ic.ram_id AND litter.animal_mother_id = ic.ewe_id";
            $litterSelectResult = $this->getConnection()->query($litterSelectSql)->fetchAll();

            $litterUpdatedCount = $this->updateLitterBySelectResult($litterSelectResult);
            $matchLitterCount += $litterUpdatedCount;
        }

        return [
            'animalsMatched' => $matchAnimalCount,
            'littersMatched' => $matchLitterCount,
            'totalsMatched' => $matchAnimalCount + $matchLitterCount,
        ];
    }

    public function matchAnimalsAndLittersGlobal() {

        do {

            $updatedCount = 0;
            $matchAnimalCount = 0;
            $matchLitterCount = 0;

            $animalSelectSql = "SELECT
                             animal.id as animal_id,
                             ic.id as inbreeding_coefficient_id
                         FROM inbreeding_coefficient ic
                         INNER JOIN (
                             SELECT
                                 id,
                                 parent_mother_id,
                                 parent_father_id
                             FROM animal
                             WHERE parent_father_id NOTNULL AND parent_mother_id NOTNULL
                                AND inbreeding_coefficient_match_updated_at ISNULL
                         )animal ON animal.parent_father_id = ic.ram_id AND animal.parent_mother_id = ic.ewe_id
                         WHERE ic.find_global_matches";
            $animalSelectResult = $this->getConnection()->query($animalSelectSql)->fetchAll();

            $animalUpdatedCount = $this->updateAnimalBySelectResult($animalSelectResult);
            $updatedCount += $animalUpdatedCount;
            $matchAnimalCount += $animalUpdatedCount;


            $litterSelectSql = "SELECT
                                 litter.id as litter_id,
                                 ic.id as inbreeding_coefficient_id
                             FROM inbreeding_coefficient ic
                             INNER JOIN (
                                 SELECT
                                     id,
                                     animal_father_id,
                                     animal_mother_id
                                 FROM litter
                                 WHERE animal_father_id NOTNULL AND animal_mother_id NOTNULL
                                    AND inbreeding_coefficient_match_updated_at ISNULL
                             )litter ON litter.animal_father_id = ic.ram_id AND litter.animal_mother_id = ic.ewe_id
                             WHERE ic.find_global_matches";
            $litterSelectResult = $this->getConnection()->query($litterSelectSql)->fetchAll();

            $litterUpdatedCount = $this->updateLitterBySelectResult($litterSelectResult);
            $updatedCount += $litterUpdatedCount;
            $matchLitterCount += $litterUpdatedCount;


            $inbreedingCoefficientIdsFromAnimals = array_unique(array_map(function(array $array) {
                return $array['inbreeding_coefficient_id'];
            }, $animalSelectResult));

            $inbreedingCoefficientIdsFromLitters = array_unique(array_map(function(array $array) {
                return $array['inbreeding_coefficient_id'];
            }, $litterSelectResult));

            $inbreedingCoefficientIds = ArrayUtil::concatArrayValues([
                $inbreedingCoefficientIdsFromAnimals, $inbreedingCoefficientIdsFromLitters
            ]);

            if (!empty($inbreedingCoefficientIds)) {
                $inbreedingCoefficientIdValuesString = SqlUtil::getIdsFilterListString($inbreedingCoefficientIds);
                $updateInbreedingCoefficientSql = "UPDATE inbreeding_coefficient SET
              find_global_matches = false,
              updated_at = NOW()
            WHERE inbreeding_coefficient.id IN ($inbreedingCoefficientIdValuesString)";
                $this->getConnection()->query($updateInbreedingCoefficientSql)->execute();
            }

        } while($updatedCount > 0);

        /*
         * Clear find_global_matched = true for inbreeding_coefficient
         * where animal or litter data was not updated, because it was not needed to be updated
         */
        $updateInbreedingCoefficientSql = "UPDATE inbreeding_coefficient SET
              find_global_matches = false,
              updated_at = NOW()
            WHERE find_global_matches = true";
        $this->getConnection()->executeQuery($updateInbreedingCoefficientSql);

        return [
            'animalsMatched' => $matchAnimalCount,
            'littersMatched' => $matchLitterCount,
            'totalsMatched' => $matchAnimalCount + $matchLitterCount,
        ];
    }

    private function updateAnimalBySelectResult(array $animalSelectResult): int {
        if (empty($animalSelectResult)) {
            return 0;
        }

        $valuesString = SqlUtil::valueStringFromNestedArray($animalSelectResult, false);

        $updateAnimalSql = "UPDATE animal SET
                  inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                  inbreeding_coefficient_match_updated_at = NOW()
                FROM (
                         VALUES $valuesString
                ) as v(animal_id, inbreeding_coefficient_id)
                WHERE animal.id = v.animal_id";
        return intval(SqlUtil::updateWithCount($this->getConnection(), $updateAnimalSql));
    }

    private function updateLitterBySelectResult(array $litterSelectResult): int {
        if (empty($litterSelectResult)) {
            return 0;
        }

        $valuesString = SqlUtil::valueStringFromNestedArray($litterSelectResult, false);

        $updateLitterSql = "UPDATE litter SET
                      inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                      inbreeding_coefficient_match_updated_at = NOW()
                    FROM (
                             VALUES $valuesString
                    ) as v(litter_id, inbreeding_coefficient_id)
                    WHERE litter.id = v.litter_id";

        return intval(SqlUtil::updateWithCount($this->getConnection(), $updateLitterSql));
    }
}
