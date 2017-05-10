<?php


namespace AppBundle\Util;


use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

class ExceptionUtil
{
    const MIN_TABLE_STRLEN = 1;
    const MAX_TABLE_STRLEN = 30;

    /**
     * @param ForeignKeyConstraintViolationException $exception
     * @return string
     */
    public static function getBlockedTableInForeignKeyConstraintViolationException(ForeignKeyConstraintViolationException $exception)
    {
        $preString = "update or delete on table \"";
        $postString = "\" violates foreign key constraint";
        $blockedTable = StringUtil::extractSandwichedSubString($exception->getMessage(), $preString, $postString);
        if(self::MIN_TABLE_STRLEN < strlen($blockedTable) && strlen($blockedTable) < self::MAX_TABLE_STRLEN) {
            return $blockedTable;
        }
        return null;
    }


    /**
     * @param ForeignKeyConstraintViolationException $exception
     * @return string
     */
    public static function getReferenceTableInForeignKeyConstraintViolationException(ForeignKeyConstraintViolationException $exception)
    {
        $exceptionMessage = $exception->getMessage();
        $exceptionMessage = preg_replace('/on table/', '', $exceptionMessage, 1);
        $exceptionMessage = strtr($exceptionMessage, ["\n" =>' ', '  ' => ' ']);
        $preString = "on table \"";
        $postString = "\" DETAIL: Key (id)=(";
        $referenceTable = StringUtil::extractSandwichedSubString($exceptionMessage, $preString, $postString);
        if(self::MIN_TABLE_STRLEN < strlen($referenceTable) && strlen($referenceTable) < self::MAX_TABLE_STRLEN) {
            return $referenceTable;
        }
        return $referenceTable;
    }
}