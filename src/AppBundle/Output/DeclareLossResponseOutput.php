<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Entity\DeclareLoss;

class DeclareLossResponseOutput
{
    /**
     * @param DeclareLoss $loss
     * @return array
     */
    public static function createHistoryResponse($loss)
    {
        $lastResponse = Utils::returnLastResponse($loss->getResponses());
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
            "date_of_death" => $loss->getDateOfDeath(),
            "reason_of_loss" => $loss->getReasonOfLoss(),
            "ubn_destructor" => $loss->getUbnDestructor(),
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
        $lastResponse = Utils::returnLastResponse($loss->getResponses());
        if($lastResponse != false) {
            $errorCode = $lastResponse->getErrorCode();
            $errorMessage = $lastResponse->getErrorMessage();
            $messageNumber = $lastResponse->getMessageNumber();
            $isRemovedByUser = $lastResponse->getIsRemovedByUser();
        } else {
            $errorCode = null;
            $errorMessage = null;
            $messageNumber = null;
            $isRemovedByUser = true;
        }

        if($isRemovedByUser) {
            return null;
        }

        $res = array(
            "request_id" => $loss->getRequestId(),
            "log_date" => $loss->getLogDate(),
            "uln_country_code" => $loss->getUlnCountryCode(),
            "uln_number" => $loss->getUlnNumber(),
            "pedigree_country_code" => $loss->getAnimal()->getPedigreeCountryCode(),
            "pedigree_number" => $loss->getAnimal()->getPedigreeNumber(),
            "date_of_death" => $loss->getDateOfDeath(),
            "reason_of_loss" => $loss->getReasonOfLoss(),
            "ubn_destructor" => $loss->getUbnDestructor(),
            "request_state" => $loss->getRequestState(),
            "error_code" => $errorCode,
            "error_message" => $errorMessage,
            "message_number" => $messageNumber
        );

        return $res;
    }


}