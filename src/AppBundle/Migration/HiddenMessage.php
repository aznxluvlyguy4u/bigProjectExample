<?php


namespace AppBundle\Migration;


use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

class HiddenMessage
{

    /**
     * @param ObjectManager $em
     * @param CommandUtil|null $cmdUtil
     */
    public static function migrateHiddenMessageStatusFromResponseToRequestBase(ObjectManager $em, CommandUtil $cmdUtil = null)
    {        
        $sql = "SELECT r.request_id, r.is_removed_by_user FROM declare_base b
                  INNER JOIN declare_base_response r ON r.request_id = b.request_id
                  WHERE r.is_removed_by_user = TRUE AND b.hide_failed_message = FALSE
                GROUP BY r.request_id, r.is_removed_by_user";
        $requestIdsWithHiddenStatus = $em->getConnection()->query($sql)->fetchAll();

        self::hideFailedMessagesByRequestId($em, $cmdUtil, $requestIdsWithHiddenStatus);
    }


    /**
     * @param ObjectManager $em
     * @param CommandUtil|null $cmdUtil
     */
    public static function hideFailedMessagesMissingAResponse(ObjectManager $em, CommandUtil $cmdUtil = null)
    {        
        $sql = "SELECT b.request_id, b.request_state, b.ubn FROM declare_base b
                  LEFT JOIN declare_base_response r ON r.request_id = b.request_id
                WHERE r.id ISNULL AND b.request_state = 'FAILED' AND b.hide_failed_message = FALSE";
        $requestIdsWithHiddenStatus = $em->getConnection()->query($sql)->fetchAll();

        self::hideFailedMessagesByRequestId($em, $cmdUtil, $requestIdsWithHiddenStatus);
    }


    /**
     * @param ObjectManager $em
     * @param CommandUtil|null $cmdUtil
     * @param array $requestIdsWithHiddenStatus
     */
    private static function hideFailedMessagesByRequestId(ObjectManager $em, CommandUtil $cmdUtil = null, $requestIdsWithHiddenStatus)
    {
        $count = count($requestIdsWithHiddenStatus);
        $count = $count > 0 ? $count : 1;

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($count, 1); }

        foreach ($requestIdsWithHiddenStatus as $requestIdWithHiddenStatus) {
            $sql = "UPDATE declare_base SET hide_failed_message = TRUE
            WHERE request_id = '".$requestIdWithHiddenStatus['request_id']."'";
            $em->getConnection()->exec($sql);

            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1); }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }
}