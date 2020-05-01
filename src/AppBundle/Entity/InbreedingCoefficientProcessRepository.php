<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;

/**
 * Class InbreedingCoefficientProcessRepository
 * @package AppBundle\Entity
 */
class InbreedingCoefficientProcessRepository extends BaseRepository {

    function getAdminProcess(): InbreedingCoefficientProcess
    {
        return $this->getProcess(InbreedingCoefficientProcessSlot::ADMIN);
    }

    function getReportProcess(): InbreedingCoefficientProcess
    {
        return $this->getProcess(InbreedingCoefficientProcessSlot::REPORT);
    }

    function getSmallProcess(): InbreedingCoefficientProcess
    {
        return $this->getProcess(InbreedingCoefficientProcessSlot::SMALL);
    }

    private function getProcess(string $slot): InbreedingCoefficientProcess
    {
        $process = $this->findOneBy(['slot' => $slot]);
        if (!$process) {
            $process = new InbreedingCoefficientProcess(
                $slot
            );
            $this->getManager()->persist($process);
            $this->getManager()->flush();

            $this->getManager()->refresh($process);
        }
        return $process;
    }


    /**
     * @return array|InbreedingCoefficientProcess[]
     */
    function getLockedProcesses(): array
    {
        return $this->findBy(['isLocked' => true]);
    }


    function unlockAllProcesses(): int
    {
        $processes = $this->getLockedProcesses();
        /** @var InbreedingCoefficientProcess $process */
        foreach ($processes as $process) {
            $process->setIsLocked(false);
            $this->getManager()->persist($process);
        }

        if (!empty($processes)) {
            $this->flush();
        }
        return count($processes);
    }



    public function isAdminProcessCancelled(): bool
    {
        return $this->isProcessCancelled(InbreedingCoefficientProcessSlot::ADMIN);
    }

    public function isReportProcessCancelled(): bool
    {
        return $this->isProcessCancelled(InbreedingCoefficientProcessSlot::REPORT);
    }

    public function isSmallProcessCancelled(): bool
    {
        return $this->isProcessCancelled(InbreedingCoefficientProcessSlot::SMALL);
    }

    private function isProcessCancelled(int $slot): bool
    {
        $sql = "SELECT is_cancelled FROM inbreeding_coefficient_process WHERE slot = $slot";
        return $this->getConnection()->query($sql)->fetchColumn() ?? false;
    }
}
