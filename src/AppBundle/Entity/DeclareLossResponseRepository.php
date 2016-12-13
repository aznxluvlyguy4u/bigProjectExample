<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\DataFixtures\ORM\MockedDeclareArrivalResponse;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\DeclareArrivalResponseOutput;
use AppBundle\Output\DeclareLossResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\DeclareLoss;

/**
 * Class DeclareLossResponseRepository
 * @package AppBundle\Entity
 */
class DeclareLossResponseRepository extends BaseRepository {


    /**
     * @param Location $location
     * @param $messageNumber
     * @return DeclareLossResponse|null
     */
    public function getLossResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedLosses = $this->_em->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->getLosses($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedLosses, $messageNumber);
    }

    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public function getLossesWithLastHistoryResponses(Location $location)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, is_export_animal,
                  a.date_of_death, reason_of_loss, ubn_destructor, request_state, 
                  r.message_number
                FROM declare_base b
                  INNER JOIN declare_loss a ON a.id = b.id
                  LEFT JOIN animal s ON s.id = a.animal_id
                  INNER JOIN (
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
                AND a.location_id = ".$locationId." ORDER BY b.log_date DESC"
        ;

        return $this->getManager()->getConnection()->query($sql)->fetchAll();
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getLossesWithLastErrorResponses(Location $location)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return []; }

        $sql = "SELECT b.request_id, log_date, a.uln_country_code, a.uln_number,
                  pedigree_country_code, pedigree_number, a.date_of_death,
                  is_export_animal, reason_of_loss, ubn_destructor,
                  request_state, hide_failed_message as is_removed_by_user,
                  r.error_code, r.error_message, r.message_number
                FROM declare_base b
                  INNER JOIN declare_loss a ON a.id = b.id
                  LEFT JOIN animal s ON s.id = a.animal_id
                  INNER JOIN (
                    SELECT y.request_id, y.error_code, y.error_message, y.message_number
                    FROM declare_base_response y
                      INNER JOIN (
                                   SELECT request_id, MAX(log_date) as log_date
                                   FROM declare_base_response
                                   GROUP BY request_id
                                 ) z ON z.log_date = y.log_date
                    )r ON r.request_id = b.request_id
                WHERE request_state = '".RequestStateType::FAILED."' AND hide_failed_message = FALSE
                AND a.location_id = ".$locationId." ORDER BY b.log_date DESC";

        return $this->getManager()->getConnection()->query($sql)->fetchAll();
    }


}