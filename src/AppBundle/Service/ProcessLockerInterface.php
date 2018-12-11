<?php


namespace AppBundle\Service;


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
}