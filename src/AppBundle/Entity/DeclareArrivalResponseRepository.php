<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareArrivalResponseOutput;
use Doctrine\DBAL\DBALException;

/**
 * Class DeclareArrivalResponseRepository
 * @package AppBundle\Entity
 */
class DeclareArrivalResponseRepository extends BaseRepository {


    /**
     * @param Location $location
     * @param $messageNumber
     * @return DeclareArrivalResponse|null
     */
    public function getArrivalResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedArrivals = $this->_em->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivals($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedArrivals, $messageNumber);
    }

    /**
     * @param Location $location
     * @param integer $page
     * @param string $query
     * @return array
     * @throws DBALException
     */
    public function getArrivalsWithLastHistoryResponses(Location $location, $page = 1, $query = '')
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $query = "%".$query."%";

        $filter = "
             WHERE (
                da.ubn_previous_owner LIKE :query OR
                CONCAT(LOWER(a.collar_color),a.collar_number) LIKE LOWER(:query) OR
                CONCAT(LOWER(a.collar_color), ' ', a.collar_number) LIKE LOWER(:query) OR
                LOWER(CONCAT(a.uln_country_code, a.uln_number)) LIKE LOWER(:query) OR
                LOWER(CONCAT(a.uln_country_code, ' ', a.uln_number)) LIKE LOWER(:query) OR
                LOWER(CONCAT(a.pedigree_country_code, a.pedigree_number)) LIKE LOWER(:query) OR
                LOWER(CONCAT(a.pedigree_country_code, ' ', a.pedigree_number)) LIKE LOWER(:query) 
              ) 
              AND request_state IN (
                '".RequestStateType::OPEN."', 
                '".RequestStateType::REVOKING."', 
                '".RequestStateType::REVOKED."', 
                '".RequestStateType::FINISHED."', 
                '".RequestStateType::FINISHED_WITH_WARNING."'
              )
              AND a.location_id = ".$locationId." 
        ";

        $joins = "
           INNER JOIN declare_arrival da ON da.id = db.id
           LEFT JOIN animal a ON a.id = da.animal_id
           INNER JOIN (
             SELECT dbr.id, dbr.request_id, dbr.message_number, dbr.error_code, dbr.error_message
             FROM declare_base_response dbr
               INNER JOIN (
                            SELECT request_id, MAX(log_date) as log_date
                            FROM declare_base_response
                            GROUP BY request_id
                          ) dbri ON dbri.log_date = dbr.log_date AND dbri.request_id = dbr.request_id
             ) r ON r.request_id = db.request_id
        ";

        $countSql = "SELECT DISTINCT a.id
             FROM declare_base db
             ".$joins."
             ".$filter."
         ";

        $sql = "SELECT DISTINCT
                    db.request_id, 
                    db.log_date, 
                    a.uln_country_code,
                    a.uln_number,
                    a.pedigree_country_code,
                    a.pedigree_number, 
                    a.is_import_animal,
                    arrival_date,
                    ubn_previous_owner, 
                    request_state, 
                    r.message_number, 
                    r.error_code, 
                    r.error_message
                FROM declare_base db
                ".$joins."
                ".$filter."
              ORDER BY db.log_date DESC, r.id DESC
              OFFSET 10 * (".$page." - 1)
              FETCH NEXT 10 ROWS ONLY"
        ;


        $countStatement = $this->getManager()->getConnection()->prepare($countSql);
        $countStatement->bindParam('query', $query);
        $countStatement->execute();

        $statement = $this->getManager()->getConnection()->prepare($sql);
        $statement->bindParam('query', $query);
        $statement->execute();

        return [
            'totalItems' => count($countStatement->fetchAll()),
            'items' => $statement->fetchAll()
        ];
    }

    /**
     * @param Location $location
     * @return array
     * @throws DBALException
     */
    public function getArrivalsWithLastErrorResponses(Location $location)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, is_import_animal,
                  arrival_date, ubn_previous_owner, request_state, hide_failed_message as is_removed_by_user,
                  r.error_code, r.error_message, r.message_number
                FROM declare_base b
                  INNER JOIN declare_arrival a ON a.id = b.id
                  INNER JOIN (
                    SELECT y.request_id, y.error_code, y.error_message, y.message_number
                    FROM declare_base_response y
                      INNER JOIN (
                                   SELECT request_id, MAX(log_date) as log_date
                                   FROM declare_base_response
                                   GROUP BY request_id
                                 ) z ON z.log_date = y.log_date AND z.request_id = y.request_id
                    )r ON r.request_id = b.request_id
                WHERE request_state = '".RequestStateType::FAILED."' 
                AND location_id = ".$locationId." ORDER BY b.log_date DESC";

        return $this->getManager()->getConnection()->query($sql)->fetchAll();
    }


}
