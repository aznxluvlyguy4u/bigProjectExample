<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareTagsTransfer;

/**
 * Class DeclareTagsTransferOutput
 */
class DeclareTagsTransferOutput extends Output
{
    /**
     * @param DeclareTagsTransfer $tagTransfer
     * @return array
     */
    public static function createPostRequestArray(DeclareTagsTransfer $tagTransfer)
    {
        $result = array("id" => $tagTransfer->getId(),
            "request_id" => $tagTransfer->getRequestId(),
            "message_id" => $tagTransfer->getMessageId(),
            "request_state" => $tagTransfer->getRequestState(),
            "action" => $tagTransfer->getAction(),
            "recovery_indicator" => $tagTransfer->getRecoveryIndicator(),
            "relation_number_keeper" => $tagTransfer->getRelationNumberKeeper(),
            "log_date" => $tagTransfer->getLogDate(),
            "ubn" => $tagTransfer->getUbn(),
            "ubn_new_owner" => $tagTransfer->getUbnNewOwner(),
            "relation_number_acceptant" => $tagTransfer->getRelationNumberAcceptant(),
            "type" => "DeclareTagsTransfer",
            "tags" => self::tagsArray($tagTransfer),
            "action_by" => self::actionByOutput($tagTransfer->getActionBy())
        );

        return $result;
    }

    /**
     * @param DeclareTagsTransfer $tagTransfer
     * @return array
     */
    private static function tagsArray(DeclareTagsTransfer $tagTransfer)
    {
        $tags = array();

        foreach($tagTransfer->getTags() as $tag) {
            $tag = array("id" => $tag->getId(),
                         "tag_status" => $tag->getTagStatus(),
                         "animal_order_number" => $tag->getAnimalOrderNumber(),
                         "order_date" => $tag->getOrderDate(),
                         "uln_country_code" => $tag->getUlnCountryCode(),
                         "uln_number" => $tag->getUlnNumber(),
                         "type" => "Tag"
            );
            $tags[] = $tag;
        }

        return $tags;
    }

}