<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareDepartResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;

/**
 * Class DeclareArrivalResponseRepository
 * @package AppBundle\Entity
 */
class DeclareDepartResponseRepository extends BaseRepository {

    /**
     * @param Location $location
     * @param $messageNumber
     * @return DeclareDepartResponse|null
     */
    public function getDepartResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedDepartures = $this->_em->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->getDepartures($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedDepartures, $messageNumber);
    }

    /**
     * @param Location $location
     * @param integer $page
     * @param string $query
     * @return array
     * @throws DBALException
     */
    public function getDeparturesWithLastHistoryResponses(Location $location, $page = 1, $query = '')
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $query = "%".$query."%";

        $joins = "
          INNER JOIN declare_depart dd ON dd.id = db.id
          INNER JOIN (
            SELECT dbr.id as response_id, dbr.request_id, dbr.message_number, dbr.error_code, dbr.error_message
            FROM declare_base_response dbr
              INNER JOIN (
                           SELECT request_id, MAX(log_date) as log_date
                           FROM declare_base_response
                           GROUP BY request_id
                         ) dbri ON dbri.log_date = dbr.log_date AND dbri.request_id = dbr.request_id
            ) r ON r.request_id = db.request_id
            LEFT JOIN animal a ON a.id = dd.animal_id
        ";

        $filter = "
            WHERE ( 
                dd.ubn_new_owner LIKE :query OR
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
                    a.is_export_animal,
                    depart_date, 
                    ubn_new_owner, 
                    reason_of_depart, 
                    request_state,
                    r.response_id,
                    r.message_number,
                    r.error_code, 
                    r.error_message
                FROM declare_base db
                ".$joins."
                ".$filter."
                ORDER BY db.log_date DESC, r.response_id DESC
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
     */
    public function getDeparturesWithLastErrorResponses(Location $location)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, depart_date,
                  is_export_animal, ubn_new_owner, reason_of_depart,
                  request_state, hide_failed_message as is_removed_by_user,
                  r.error_code, r.error_message, r.message_number
                FROM declare_base b
                  INNER JOIN declare_depart a ON a.id = b.id
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

        return $this->getConnection()->query($sql)->fetchAll();
    }

}
