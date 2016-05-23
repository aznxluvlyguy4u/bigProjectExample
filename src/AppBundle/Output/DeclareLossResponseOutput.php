<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareLoss;

class DeclareLossResponseOutput
{
    /**
     * @param DeclareLoss $loss
     * @return array
     */
    public static function createHistoryResponse($loss)
    {
        $lastResponse = $loss->getResponses()->last();
        if($lastResponse) {
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }

        return array(
            "request_id" => $loss->getRequestId(),
            "log_date" => $loss->getLogDate(),
            "uln_country_code" => $loss->getUlnCountryCode(),
            "uln_number" => $loss->getUlnNumber(),
            "pedigree_country_code" => $loss->getAnimal()->getPedigreeCountryCode(),
            "pedigree_number" => $loss->getAnimal()->getPedigreeNumber(),
            "loss_date" => $loss->getAnimal()->getDateOfDeath(),
            "reason_of_loss" => $loss->getReasonOfLoss(),
            "ubn_processor" => "000000", //TODO add ubnProcessor field to DeclareLoss
            "request_state" => $loss->getRequestState(),
            "message_number" => $messageNumber
        );
    }

    /**
     * @param DeclareLoss $loss
     * @return array
     */
    public static function createErrorResponse($loss)
    {
        $lastResponse = $loss->getResponses()->last();
        if($lastResponse) {
            $errorCode = $lastResponse->getErrorCode();
            $errorMessage = $lastResponse->getErrorMessage();
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $errorCode = null;
            $errorMessage = null;
            $messageNumber = null;
        }

        $res = array(
            "request_id" => $loss->getRequestId(),
            "log_date" => $loss->getLogDate(),
            "uln_country_code" => $loss->getUlnCountryCode(),
            "uln_number" => $loss->getUlnNumber(),
            "pedigree_country_code" => $loss->getAnimal()->getPedigreeCountryCode(),
            "pedigree_number" => $loss->getAnimal()->getPedigreeNumber(),
            "loss_date" => $loss->getAnimal()->getDateOfDeath(),
            "reason_of_loss" => $loss->getReasonOfLoss(),
            "ubn_processor" => "000000", //TODO add ubnProcessor field to DeclareLoss
            "request_state" => $loss->getRequestState(),
            "error_code" => $errorCode,
            "error_message" => $errorMessage,
            "message_number" => $messageNumber
        );

        return $res;
    }


}