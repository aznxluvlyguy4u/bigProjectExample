<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareImportResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareImportResponseRepository
 * @package AppBundle\Entity
 */
class DeclareImportResponseRepository extends BaseRepository {
    /**
     * @param Location $location
     * @param $messageNumber
     * @return DeclareImportResponse|null
     */
    public function getImportResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedImports = $this->_em->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImports($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedImports, $messageNumber);
    }

    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public function getImportsWithLastHistoryResponses(Location $location)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, is_import_animal,
                  import_date as arrival_date, animal_country_origin as country_origin, animal_uln_number_origin, request_state, 
                  r.message_number
                FROM declare_base b
                  INNER JOIN declare_import a ON a.id = b.id
                  LEFT JOIN (
                    SELECT y.request_id, y.message_number
                    FROM declare_base_response y
                      INNER JOIN (
                                   SELECT request_id, MAX(log_date) as log_date
                                   FROM declare_base_response
                                   GROUP BY request_id
                                 ) z ON z.log_date = y.log_date
                    )r ON r.request_id = b.request_id
                WHERE (request_state = '".RequestStateType::OPEN."' OR
                      request_state = '".RequestStateType::REVOKING."' OR
                      request_state = '".RequestStateType::REVOKED."' OR
                      request_state = '".RequestStateType::FINISHED."' OR
                      request_state = '".RequestStateType::FINISHED_WITH_WARNING."')
                AND location_id = ".$locationId." ORDER BY b.log_date DESC"
        ;

        return $this->getManager()->getConnection()->query($sql)->fetchAll();
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getImportsWithLastErrorResponses(Location $location)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, is_import_animal,
                  import_date as arrival_date, animal_country_origin as country_origin, animal_uln_number_origin, request_state, hide_failed_message as is_removed_by_user,
                  r.error_code, r.error_message, r.message_number
                FROM declare_base b
                  INNER JOIN declare_import a ON a.id = b.id
                  LEFT JOIN (
                    SELECT y.request_id, y.error_code, y.error_message, y.message_number
                    FROM declare_base_response y
                      INNER JOIN (
                                   SELECT request_id, MAX(log_date) as log_date
                                   FROM declare_base_response
                                   GROUP BY request_id
                                 ) z ON z.log_date = y.log_date
                    )r ON r.request_id = b.request_id
                WHERE request_state = '".RequestStateType::FAILED."' 
                AND location_id = ".$locationId." ORDER BY b.log_date DESC";

        return $this->getManager()->getConnection()->query($sql)->fetchAll();
    }
}