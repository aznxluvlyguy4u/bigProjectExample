<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\ProcessStatusType;

/**
 * Class ProcessStatusRepository
 * @package AppBundle\Entity
 */
class ProcessStatusRepository extends BaseRepository {

    function getAnimalResidenceDailyMatchWithCurrentLivestockProcess(): ProcessStatus
    {
        return $this->getProcess(ProcessStatusType::ANIMAL_RESIDENCE_DAILY_MATCH_WITH_CURRENT_LIVESTOCK);
    }

    private function getProcess(int $type): ProcessStatus
    {
        $process = $this->findOneBy(['type' => $type]);
        if (!$process) {
            $process = new ProcessStatus(
                $type
            );
            $this->getManager()->persist($process);
            $this->getManager()->flush();

            $this->getManager()->refresh($process);
        }
        return $process;
    }


    /**
     * @return array|ProcessStatus[]
     */
    function getLockedProcesses(): array
    {
        return $this->findBy(['isLocked' => true]);
    }


    function unlockAllProcesses(): int
    {
        $processes = $this->getLockedProcesses();
        /** @var ProcessStatus $process */
        foreach ($processes as $process) {
            $process->setIsLocked(false);
            $this->getManager()->persist($process);
        }

        if (!empty($processes)) {
            $this->flush();
        }
        return count($processes);
    }


    public function isAnimalResidenceDailyMatchWithCurrentLivestockProcessCancelled(): bool
    {
        return $this->isProcessCancelled(ProcessStatusType::ADMIN);
    }

    private function isProcessCancelled(int $type): bool
    {
        $sql = "SELECT is_cancelled FROM process_status WHERE type = $type";
        return $this->getConnection()->query($sql)->fetchColumn() ?? false;
    }
}
