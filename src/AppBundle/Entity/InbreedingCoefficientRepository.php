<?php

namespace AppBundle\Entity;

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
     * @param int $ramId
     * @param int $eweId
     * @return InbreedingCoefficient|null
     */
    function findByPair(int $ramId, int $eweId): ?InbreedingCoefficient {
        return $this->findOneBy([
           'ram' => $ramId,
           'ewe' => $eweId
        ]);
    }
}