<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareExportResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareExportResponseRepository
 * @package AppBundle\Entity
 */
class DeclareExportResponseRepository extends BaseRepository {
    /**
     * @param Location $location
     * @param $messageNumber
     * @return DeclareExportResponse|null
     */
    public function getExportResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedExports = $this->_em->getRepository(Constant::DECLARE_EXPORT_REPOSITORY)->getExports($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedExports, $messageNumber);
    }

    /**
     * @param Location $location
     * @param integer $page
     * @return ArrayCollection
     */
    public function getExportsWithLastHistoryResponses(Location $location, $page = 1)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $countSql = "SELECT COUNT(*) AS totalitems
                FROM declare_base b
                  INNER JOIN declare_export a ON a.id = b.id
                  INNER JOIN (
                    SELECT y.request_id, y.message_number, y.error_code, y.error_message
                    FROM declare_base_response y
                      INNER JOIN (
                                   SELECT request_id, MAX(log_date) as log_date
                                   FROM declare_base_response
                                   GROUP BY request_id
                                 ) z ON z.log_date = y.log_date AND z.request_id = y.request_id
                    )r ON r.request_id = b.request_id
                WHERE (request_state = '".RequestStateType::OPEN."' OR
                      request_state = '".RequestStateType::REVOKING."' OR
                      request_state = '".RequestStateType::REVOKED."' OR
                      request_state = '".RequestStateType::FINISHED."' OR
                      request_state = '".RequestStateType::FINISHED_WITH_WARNING."')
                AND location_id = ".$locationId."
                GROUP BY location_id";

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, is_export_animal,
                  export_date as depart_date, reason_of_export as reason_of_depart, request_state, 
                  r.message_number, r.error_code, r.error_message
                FROM declare_base b
                  INNER JOIN declare_export a ON a.id = b.id
                  INNER JOIN (
                    SELECT y.request_id, y.message_number, y.error_code, y.error_message
                    FROM declare_base_response y
                      INNER JOIN (
                                   SELECT request_id, MAX(log_date) as log_date
                                   FROM declare_base_response
                                   GROUP BY request_id
                                 ) z ON z.log_date = y.log_date AND z.request_id = y.request_id
                    )r ON r.request_id = b.request_id
                WHERE (request_state = '".RequestStateType::OPEN."' OR
                      request_state = '".RequestStateType::REVOKING."' OR
                      request_state = '".RequestStateType::REVOKED."' OR
                      request_state = '".RequestStateType::FINISHED."' OR
                      request_state = '".RequestStateType::FINISHED_WITH_WARNING."')
                AND location_id = ".$locationId." ORDER BY b.log_date DESC
                OFFSET 10 * (".$page." - 1)
                FETCH NEXT 10 ROWS ONLY"
        ;

         $totalItems = 0;

         $countResult = $this->getManager()->getConnection()->query($countSql)->fetchAll();

         if (!empty($countResult)) {
             $totalItems = $countResult[0]['totalitems'];
         }

         return [
             'totalItems' => $totalItems,
             'items' => $this->getManager()->getConnection()->query($sql)->fetchAll()
         ];
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getExportsWithLastErrorResponses(Location $location)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, export_date as depart_date,
                  is_export_animal, reason_of_export as reason_of_depart,
                  request_state, hide_failed_message as is_removed_by_user,
                  r.error_code, r.error_message, r.message_number
                FROM declare_base b
                  INNER JOIN declare_export a ON a.id = b.id
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
