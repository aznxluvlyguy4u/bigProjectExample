<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareLoss;

/**
 * Class DeclareLossOutput
 */
class DeclareLossOutput
{
    /**
     * @param DeclareLoss $loss
     * @return array
     */
    public static function createPostRequestArray(DeclareLoss $loss)
    {
        $result = array("id" => $loss->getId(),
            "request_id" => $loss->getRequestId(),
            "message_id" => $loss->getMessageId(),
            "request_state" => $loss->getRequestState(),
            "action" => $loss->getAction(),
            "recovery_indicator" => $loss->getRecoveryIndicator(),
            "relation_number_keeper" => $loss->getRelationNumberKeeper(),
            "log_date" => $loss->getLogDate(),
            "ubn" => $loss->getUbn(),
            "uln_country_code" => $loss->getUlnCountryCode(),
            "uln_number" => $loss->getUlnNumber(),
            "date_of_death" => $loss->getDateOfDeath(),
            "reason_of_loss" => $loss->getReasonOfLoss()
        );

        return $result;
    }


    public static function createUpdateRequestArray(DeclareLoss $loss)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($loss);
    }


}