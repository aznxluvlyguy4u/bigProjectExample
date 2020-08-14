<?php


namespace AppBundle\Service;


use Psr\Log\LoggerInterface;

interface ProcessLockerInterface
{
    function initializeProcessGroupValues(string $processGroupName, int $maxProcesses = 1): string;
    function isProcessLimitReached(string $processGroupName): bool;
    function addProcess(string $processGroupName): int;
    function isProcessRunning(string $processGroupName, int $processId): bool;
    function getProcessesCount(string $processGroupName, bool $logValues = true);
    function removeProcess(string $processGroupName, int $processId);
    function removeAllProcessesOfGroup(string $processGroupName);
    function removeAllProcesses();
    function getMaxLimit(string $processGroupName): int;

    /*
     * functions used in queue services
     */
    function isProcessLimitNotReachedCheckForQueueService(string $processGroupName, LoggerInterface $logger): array;
    function unlockProcessForQueueService(string $processGroupName, int $processId, LoggerInterface $logger);

}