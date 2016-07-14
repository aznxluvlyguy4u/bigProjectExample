<?php

namespace AppBundle\Output;


use AppBundle\Entity\TagTransferItemRequest;
use AppBundle\Entity\TagTransferItemResponse;

class DeclareTagsTransferResponseOutput
{
    /**
     * @param TagTransferItemRequest $tagTransfer
     * @param TagTransferItemResponse $tagTransferLastResponse
     * @return array
     */
    public static function createHistoryResponse($tagTransfer, $tagTransferLastResponse = null)
    {
        if($tagTransferLastResponse != null) {
            $messageNumber = $tagTransferLastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }

        return array(
            "request_id" => $tagTransfer->getRequestId(),
            "log_date" => $tagTransfer->getLogDate(),
            "uln_country_code" => $tagTransfer->getUlnCountryCode(),
            "uln_number" => $tagTransfer->getUlnNumber(),
            "ubn_new_owner" => $tagTransfer->getUbnNewOwner(),
            "request_state" => $tagTransfer->getRequestState(),
            "message_number" => $messageNumber
        );
    }

    /**
     * @param TagTransferItemRequest $tagTransfer
     * @param TagTransferItemResponse $tagTransferLastResponse
     * @return array
     */
    public static function createErrorResponse($tagTransfer, $tagTransferLastResponse = null)
    {
        if($tagTransferLastResponse != null) {
            $errorCode = $tagTransferLastResponse->getErrorCode();
            $errorMessage = $tagTransferLastResponse->getErrorMessage();
            $messageNumber = $tagTransferLastResponse->getMessageNumber();
            $isRemovedByUser = $tagTransferLastResponse->getIsRemovedByUser();
        } else {
            $errorCode = null;
            $errorMessage = null;
            $messageNumber = null;
            $isRemovedByUser = true;
        }

        $res = array(
            "request_id" => $tagTransfer->getRequestId(),
            "log_date" => $tagTransfer->getLogDate(),
            "uln_country_code" => $tagTransfer->getUlnCountryCode(),
            "uln_number" => $tagTransfer->getUlnNumber(),
            "ubn_new_owner" => $tagTransfer->getUbnNewOwner(),
            "request_state" => $tagTransfer->getRequestState(),
            "error_code" => $errorCode,
            "error_message" => $errorMessage,
            "is_removed_by_user" => $isRemovedByUser,
            "message_number" => $messageNumber
        );

        return $res;
    }


}