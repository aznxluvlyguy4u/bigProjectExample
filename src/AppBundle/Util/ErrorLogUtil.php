<?php


namespace AppBundle\Util;

use Doctrine\DBAL\Connection;

/**
 * Class ErrorLogUtil
 * @package AppBundle\Util
 */
class ErrorLogUtil
{
    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateTagSyncErrorLogIsFixedStatuses(Connection $conn)
    {
        $sql = "UPDATE tag_sync_error_log SET is_fixed = TRUE
                WHERE id IN (
                  SELECT l.id
                  FROM tag_sync_error_log l
                    LEFT JOIN animal a ON l.uln_number = a.uln_number AND l.uln_country_code = a.uln_country_code
                  WHERE a.id ISNULL AND is_fixed = FALSE
                )";
        return SqlUtil::updateWithCount($conn, $sql);
    }
}