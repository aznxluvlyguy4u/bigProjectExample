<?php


namespace AppBundle\Entity;


use Psr\Log\LoggerInterface;

class CalcTableBaseRepository extends BaseRepository
{
    private function log(?LoggerInterface $logger, string $message)
    {
        if ($logger) {
            $logger->notice($message);
        }
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
