<?php

namespace AppBundle\Entity;

use AppBundle\Util\Translation;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;

/**
 * Class TreatmentRepository
 * @package AppBundle\Entity
 */
class TreatmentRepository extends BaseRepository {

    /**
     * @param $ubn
     * @param int $page
     * @param int $perPage
     * @param string $searchQuery
     * @return array
     * @throws DBALException
     */
    public function getHistoricTreatments($ubn, $page = 1, $perPage = 10, $searchQuery = '')
    {
        $searchQuery = "%".$searchQuery."%";

        $filter = "
            WHERE l.ubn = :ubn
            AND (
                LOWER(t.description) LIKE LOWER(:query) OR 
                a.animal_order_number LIKE LOWER(:query) OR 
                LOWER(tm.name) LIKE LOWER(:query) OR 
                a.collar_number LIKE LOWER(:query) OR 
                LOWER(a.collar_color) LIKE LOWER(:query) OR 
                CONCAT(LOWER(a.collar_color), a.collar_number) LIKE LOWER(:query) OR
                CONCAT(LOWER(a.collar_color), ' ', a.collar_number) LIKE LOWER(:query)
            )
        ";

        $joins = "
            INNER JOIN location l ON t.location_id = l.id
            INNER JOIN treatment_animal ta ON ta.treatment_id = t.id
            INNER JOIN animal a ON a.id = ta.animal_id
            LEFT JOIN medication_selection ms ON ms.treatment_id = t.id
            LEFT JOIN treatment_medication tm ON tm.id = ms.treatment_medication_id
        ";

        $countSql = "
            SELECT DISTINCT t.id FROM treatment t
            ".$joins."
            ".$filter."
        ";

        $sql = "
            SELECT DISTINCT 
                t.id as treatment_id,
                t.create_date,
                t.description,
                t.start_date,
                t.end_date,
                t.revoke_date,
                t.type,
                t.status
            FROM treatment t
            ".$joins."
            ".$filter."
            ORDER BY t.create_date DESC
            OFFSET ".$perPage." * (".$page." - 1)
            FETCH NEXT ".$perPage." ROWS ONLY
        ";

        $countStatement = $this->getManager()->getConnection()->prepare($countSql);
        $countStatement->bindParam('ubn', $ubn);
        $countStatement->bindParam('query', $searchQuery);
        $countStatement->execute();

        $statement = $this->getManager()->getConnection()->prepare($sql);
        $statement->bindParam('ubn', $ubn);
        $statement->bindParam('query', $searchQuery);
        $statement->execute();

        $results = [];

        foreach ($statement->fetchAll() as $item) {
            $medicationSql = '
                SELECT
                    tm.name,
                    ms.waiting_time_end,
                    tm.dosage,
                    tm.dosage_unit,
                    tm.reg_nl,
                    tm.treatment_duration
               FROM medication_selection ms 
               LEFT JOIN treatment_medication tm ON ms.treatment_medication_id = tm.id
               WHERE ms.treatment_id = :id
            ';
            $medicineStatement = $this->getManager()->getConnection()->prepare($medicationSql);
            $medicineStatement->bindParam('id', $item['treatment_id']);
            $medicineStatement->execute();

            $animalSql = '
                SELECT a.* FROM treatment_animal ta
                LEFT JOIN animal a ON ta.animal_id = a.id
                WHERE ta.treatment_id = :treatment_id
            ';

            $animalStatement = $this->getManager()->getConnection()->prepare($animalSql);
            $animalStatement->bindParam('treatment_id', $item['treatment_id']);
            $animalStatement->execute();

            $item['medications'] = $medicineStatement->fetchAll();

            $item['animals'] = $animalStatement->fetchAll();

            $item['dutchType'] = Translation::getDutchTreatmentType($item['type']);
            $results[] = $item;
        }

        return [
            'items'      => $results,
            'totalItems' => count($countStatement->fetchAll())
        ];
    }

    /**
     * @param Location $location
     * @return mixed
     */
    public function getLastTreatmentOfLocation(Location $location)
    {
        return $this->createQueryBuilder('treatment')
            ->innerJoin('treatment.location', 'location')
            ->where('location = :location')
            ->setParameter('location', $location)
            ->orderBy('treatment.createDate', 'DESC')
            ->getQuery()
            ->getResult()[0];
    }
}
