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

        $countSql = "
            SELECT COUNT(t.id) AS total FROM treatment t
            INNER JOIN location l ON t.location_id = l.id 
            WHERE l.ubn = :ubn
            AND t.description LIKE :query
            GROUP BY l.id
        ";

        $sql = '
            SELECT 
                t.id as treatment_id,
                t.create_date,
                t.description,
                t.start_date,
                t.end_date,
                t.type,
                t.status
            FROM treatment t
            INNER JOIN location l ON t.location_id = l.id
            WHERE l.ubn = :ubn
            AND t.description LIKE :query
            ORDER BY t.create_date DESC
            OFFSET '.$perPage.' * ('.$page.' - 1)
            FETCH NEXT '.$perPage.' ROWS ONLY
        ';

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
            'totalItems' => $countStatement->fetch()['total']
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
