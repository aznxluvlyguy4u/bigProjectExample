<?php


namespace AppBundle\Util;


use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Psr\Log\LoggerInterface;

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


    /**
     * @param string $errorMessage
     * @param string $columnName
     * @param bool $isInteger
     * @return int|string|null
     */
    public static function getDuplicateKeyValue(string $errorMessage, string $columnName, bool $isInteger)
    {
        $duplicateValue = StringUtil::extractSandwichedSubString(
            $errorMessage,
            "DETAIL:  Key ($columnName)=(",
            ") already exists."
        );

        if (empty($duplicateValue)) {
            return null;
        }

        if ($isInteger && ctype_digit($duplicateValue)) {
            return intval($duplicateValue);
        }

        return $duplicateValue;
    }


    /**
     * @param LoggerInterface $logger
     * @param \Throwable $exception
     */
    public static function logException(LoggerInterface $logger, \Throwable $exception)
    {
        $logger->error($exception->getMessage());
        $logger->error($exception->getTraceAsString());
    }
}