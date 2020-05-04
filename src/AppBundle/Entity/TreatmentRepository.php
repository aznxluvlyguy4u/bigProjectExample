<?php

namespace AppBundle\Entity;

use AppBundle\Util\Translation;
use Doctrine\DBAL\DBALException;

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
            SELECT * FROM treatment t
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
            $item['dutchType'] = Translation::getDutchTreatmentType($item['type']);
            $results[] = $item;
        }

        return [
            'items'      => $results,
            'totalItems' => $countStatement->fetch()['total']
        ];
    }
}