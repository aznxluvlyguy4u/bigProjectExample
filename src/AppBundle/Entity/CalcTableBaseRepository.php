<?php


namespace AppBundle\Entity;


use AppBundle\Util\LoggerUtil;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class CalcTableBaseRepository extends BaseRepository
{
    private function log(?LoggerInterface $logger, string $message)
    {
        LoggerUtil::log($message, $logger, LogLevel::DEBUG);
    }

    protected function logClearingTable(?LoggerInterface $logger, string $tableName)
    {
        $this->log($logger, "Clear $tableName table");
    }

    protected function logFillingTableStart(?LoggerInterface $logger, string $tableName, string $suffix = '')
    {
        $this->log($logger, "Fill $tableName table$suffix");
    }

    protected function logFillingTableEnd(?LoggerInterface $logger, string $tableName)
    {
        $this->log($logger, "$tableName table filled!");
    }
}
