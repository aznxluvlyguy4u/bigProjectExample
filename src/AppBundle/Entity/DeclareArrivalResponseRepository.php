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

        $countSql = "SELECT COUNT(*) AS totalitems
             FROM declare_base b
               INNER JOIN declare_arrival a ON a.id = b.id
               INNER JOIN (
                 SELECT y.request_id, y.message_number, y.error_code, y.error_message
                 FROM declare_base_response y
                   INNER JOIN (
                                SELECT request_id, MAX(log_date) as log_date
                                FROM declare_base_response
                                GROUP BY request_id
                              ) z ON z.log_date = y.log_date AND z.request_id = y.request_id
                 )r ON r.request_id = b.request_id
             WHERE (
                    a.ubn_previous_owner LIKE :query OR
                    a.uln_number LIKE :query OR 
                    a.pedigree_number LIKE :query OR 
                    a.pedigree_country_code LIKE :query OR 
                    a.uln_country_code LIKE :query 
                  ) 
                  AND request_state IN (
                    '".RequestStateType::OPEN."', 
                    '".RequestStateType::REVOKING."', 
                    '".RequestStateType::REVOKED."', 
                    '".RequestStateType::FINISHED."', 
                    '".RequestStateType::FINISHED_WITH_WARNING."'
                  )
                  AND location_id = ".$locationId." 
                  GROUP BY location_id"
         ;

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, is_import_animal,
                  arrival_date, ubn_previous_owner, request_state, 
                  r.message_number, r.error_code, r.error_message
                FROM declare_base b
                  INNER JOIN declare_arrival a ON a.id = b.id
                  INNER JOIN (
                    SELECT y.request_id, y.message_number, y.error_code, y.error_message
                    FROM declare_base_response y
                      INNER JOIN (
                                   SELECT request_id, MAX(log_date) as log_date
                                   FROM declare_base_response
                                   GROUP BY request_id
                                 ) z ON z.log_date = y.log_date AND z.request_id = y.request_id
                    )r ON r.request_id = b.request_id
                WHERE (
                        a.ubn_previous_owner LIKE :query OR
                        a.uln_country_code LIKE :query OR
                        a.uln_number LIKE :query OR 
                        a.pedigree_country_code LIKE :query OR
                        a.pedigree_number LIKE :query
                      ) 
                      AND request_state IN (
                        '".RequestStateType::OPEN."', 
                        '".RequestStateType::REVOKING."', 
                        '".RequestStateType::REVOKED."', 
                        '".RequestStateType::FINISHED."', 
                        '".RequestStateType::FINISHED_WITH_WARNING."'
                      )
                      AND location_id = ".$locationId." 
              ORDER BY b.log_date DESC
              OFFSET 10 * (".$page." - 1)
              FETCH NEXT 10 ROWS ONLY"
        ;

        $totalItems = 0;

        $statement = $this->getManager()->getConnection()->prepare($countSql);
        $statement->bindParam('query', $query);
        $statement->execute();
        $countResult = $statement->fetchAll();

        if (!empty($countResult)) {
            $totalItems = $countResult[0]['totalitems'];
        }

        $statement = $this->getManager()->getConnection()->prepare($sql);
        $statement->bindParam('query', $query);
        $statement->execute();

        return [
            'totalItems' => $totalItems,
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
