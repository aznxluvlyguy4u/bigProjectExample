<?php


namespace AppBundle\Service;


use AppBundle\Enumerator\ProcessType;
use AppBundle\Exception\ProcessLockerException;
use AppBundle\Util\ArrayUtil;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

class ProcessLocker implements ProcessLockerInterface
{
    const DEFAULT_MAX_PROCESSES = 1;

    /** @var Logger */
    private $logger;
    /** @var SettingsContainer */
    private $settingsContainer;

    /** @var Filesystem */
    private $fs;
    /** @var string[] */
    private $lockFilePaths;
    /** @var int[] */
    private $maxProcessCounts;

    public function __construct(Logger $logger, SettingsContainer $settingsContainer)
    {
        $this->logger = $logger;
        $this->settingsContainer = $settingsContainer;

        $this->fs = new Filesystem();
        $this->lockFilePaths = [];
        $this->maxProcessCounts = [];
    }


    /**
     * @param string $processGroupName
     * @param int $maxProcesses priority: this argument, env parameter, default value of 1
     * @return string
     * @throws ProcessLockerException
     */
    function initializeProcessGroupValues(string $processGroupName, int $maxProcesses = 0): string
    {
        if (!ctype_alnum($processGroupName)) {
            $this->logAndThrowException('ProcessGroupName can only contain numbers or letters: ' . $processGroupName);
        }

        if ($maxProcesses <= 0) {
            switch ($processGroupName) {
                case ProcessType::SQS_FEEDBACK_WORKER:
                    $maxProcesses = $this->settingsContainer->getMaxFeedbackWorkers();
                    break;
                default:
                    $maxProcesses = self::DEFAULT_MAX_PROCESSES;
                    break;
            }
        }

        $this->lockFilePaths[$processGroupName] = sys_get_temp_dir(). DIRECTORY_SEPARATOR . $processGroupName . '.lock';
        $this->maxProcessCounts[$processGroupName] = $maxProcesses;
        return $this->lockFilePaths[$processGroupName];
    }


    /**
     * @param string $processGroupName
     * @return bool
     * @throws ProcessLockerException
     */
    function isProcessLimitReached(string $processGroupName): bool
    {
        $currentProcessCount = $this->getProcessesCount($processGroupName, false);
        $maxProcessCount = ArrayUtil::get($processGroupName, $this->maxProcessCounts, 1);
        $this->logger->info($processGroupName . ' processes [current|max]: '
            . $currentProcessCount . '|' . $maxProcessCount);
        return $currentProcessCount >= $maxProcessCount;
    }


    /**
     * @param string $processGroupName
     * @param bool $logValues
     * @return int
     * @throws ProcessLockerException
     */
    function getProcessesCount(string $processGroupName, bool $logValues = true)
    {
        $currentProcessCount = count($this->getProcesses($processGroupName));

        if ($logValues) {
            $maxProcessCount = ArrayUtil::get($processGroupName, $this->maxProcessCounts, 1);
            $this->logger->notice($processGroupName . ' processes [current|max]: '
                . $currentProcessCount . '|' . $maxProcessCount);
        }
        return $currentProcessCount;
    }


    /**
     * @param string $processGroupName
     * @return int
     * @throws ProcessLockerException
     */
    function addProcess(string $processGroupName): int
    {
        $processes = $this->getProcesses($processGroupName);
        $newProcessId = $this->getNextProcessKey($processes);
        $processes[$newProcessId] = $newProcessId;

        $this->updateLockFileByGroupName($processGroupName, $processes);
        $this->logger->info('Added process ' . $newProcessId);

        return $newProcessId;
    }


    /**
     * @param string $processGroupName
     * @param int $processId
     * @return bool
     * @throws ProcessLockerException
     */
    function isProcessRunning(string $processGroupName, int $processId): bool
    {
        $processes = $this->getProcesses($processGroupName);
        return key_exists($processId, $processes);
    }

    /**
     * @param string $processGroupName
     * @param int $processId
     * @throws ProcessLockerException
     */
    function removeProcess(string $processGroupName, int $processId)
    {
        $processes = $this->getProcesses($processGroupName);
        if (key_exists($processId, $processes)) {
            unset($processes[$processId]);
            $this->updateLockFileByGroupName($processGroupName, $processes);
            $this->logger->info('Removed process, processId: ' . $processId);
        } else {
            $this->logger->info('Process is not locked, processId: ' . $processId);
        }
    }


    /**
     * @param string $processGroupName
     * @throws ProcessLockerException
     */
    public function removeAllProcessesOfGroup(string $processGroupName)
    {
        $lockFilePath = $this->getLockFilePath($processGroupName);
        $this->removeLockFile($lockFilePath);
        unset($this->lockFilePaths[$processGroupName]);
        unset($this->maxProcessCounts[$processGroupName]);
    }


    /**
     * @throws ProcessLockerException
     */
    public function removeAllProcesses()
    {
        foreach ($this->lockFilePaths as $processGroupName => $lockFilePath)
        {
            $this->removeAllProcessesOfGroup($processGroupName);
        }
    }


    /**
     * @param array $processes
     * @return int
     */
    private function getNextProcessKey(array $processes): int
    {
        return empty($processes) ? 1 : ArrayUtil::lastKey($processes,true) + 1;
    }


    /**
     * @param string $processGroupName
     * @param bool $createNewIfMissing
     * @return string
     * @throws ProcessLockerException
     */
    private function getLockFilePath(string $processGroupName, bool $createNewIfMissing = false): string
    {
        $lockFilePath = ArrayUtil::get($processGroupName, $this->lockFilePaths, null);
        if ($lockFilePath) {
            return $lockFilePath;
        }
        if (!$createNewIfMissing) {
            $this->logAndThrowException('Lockfile path has not yet been set for group: '.$processGroupName);
        }
        return $this->initializeProcessGroupValues($processGroupName);
    }


    /**
     * @param string $lockFilePath
     * @return array|null
     */
    private function getLockFileContent(string $lockFilePath): ?array
    {
        if (!$this->fs->exists($lockFilePath)) {
            $this->logger->notice('Creating new lock file: ' . $lockFilePath);
            $this->updateLockFile($lockFilePath, []);
        }

        $this->logger->info('Get lockfile info from: '.$lockFilePath);
        $content = file_get_contents($lockFilePath);
        $this->logger->info('Lockfile content: ' . ($content ? $content : 'empty'));
        return json_decode($content, true);
    }


    /**
     * @param string $processGroupName
     * @param array $processes
     * @throws ProcessLockerException
     */
    private function updateLockFileByGroupName(string $processGroupName, array $processes)
    {
        $lockFilePath = $this->getLockFilePath($processGroupName, false);
        $this->updateLockFile($lockFilePath, $processes);
    }


    /**
     * @param string $lockFilePath
     */
    private function removeLockFile(string $lockFilePath)
    {
        if ($this->fs->exists($lockFilePath)) {
            $this->fs->remove($lockFilePath);
            $this->logger->notice('Removed lock file: '.$lockFilePath);
        } else {
            $this->logger->notice('Lock file has already been removed: '.$lockFilePath);
        }
    }


    /**
     * @param string $lockFilePath
     * @param array $processes
     */
    private function updateLockFile(string $lockFilePath, array $processes)
    {
        file_put_contents(
            $lockFilePath,
            json_encode($processes)
            );
    }


    /**
     * @param string $processGroupName
     * @return array
     * @throws ProcessLockerException
     */
    private function getProcesses(string $processGroupName): array
    {
        $processes = $this->getLockFileContent($this->getLockFilePath($processGroupName));
        return $processes ? $processes : [];
    }


    /**
     * @param string $message
     * @param int $code
     * @throws ProcessLockerException
     */
    private function logAndThrowException(string $message, int $code = 500)
    {
        $errorMessage = 'PROCESS LOCKER ['.$code.']: '.$message;
        $this->logger->error($errorMessage);
        throw new ProcessLockerException($errorMessage, $code);
    }
}