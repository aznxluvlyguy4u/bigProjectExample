<?php

namespace AppBundle\Command;

use AppBundle\Enumerator\ProcessType;
use AppBundle\Service\ProcessLockerInterface;
use AppBundle\Util\CommandUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoProcessLockerCommand extends ContainerAwareCommand
{
    const DEFAULT_OPTION = 1;
    const TITLE = 'Process Locker';

    /** @var CommandUtil */
    private $cmdUtil;

    protected function configure()
    {
        $this
            ->setName('nsfo:process:locker')
            ->setDescription(self::TITLE)
            ->addOption('option', 'o', InputOption::VALUE_OPTIONAL,
                'Process options: 1 = Display all, 2 = Unlock all, 3 = Display feedback worker, 4 = Unlock feedback worker')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cmdUtil = new CommandUtil($input, $output, $this->getHelper('question'));

        $option = intval(ltrim($input->getOption('option'),'='));

        switch ($option) {
            case 1:
                $this->writeLn('1: Display all processes');
                $this->displayAllLockedProcesses();
                break;

            case 2:
                $this->writeLn('2: Unlock  all processes');
                $this->unlockAllProcesses();
                break;

            case 3:
                $this->writeLn('3: Display feedback worker processes');
                $this->displayLockedProcesses(ProcessType::SQS_FEEDBACK_WORKER);
                break;

            case 4:
                $this->writeLn('4: Unlock  feedback worker processes');
                $this->unlockWorkerProcesses(ProcessType::SQS_FEEDBACK_WORKER);
                break;

            default: $this->displayAllLockedProcesses(); return;
        }

        $output->writeln('Command result.');
    }


    private function displayAllLockedProcesses()
    {
        foreach (ProcessType::getConstants() as $processType) {
            $this->displayLockedProcesses($processType);
        }
    }

    private function displayLockedProcesses($processType)
    {
        $this->getProcessLocker()->initializeProcessGroupValues($processType);
        $this->getProcessLocker()->getProcessesCount($processType, true);
    }

    private function unlockAllProcesses()
    {
        foreach (ProcessType::getConstants() as $processType) {
            $this->unlockWorkerProcesses($processType);
        }
    }

    private function unlockWorkerProcesses($processType)
    {
        $this->getProcessLocker()->initializeProcessGroupValues($processType);
        $this->getProcessLocker()->getProcessesCount($processType, true);
        $this->getProcessLocker()->removeAllProcessesOfGroup($processType);
    }


    /**
     * @return ProcessLockerInterface
     */
    public function getProcessLocker()
    {
        return $this->getContainer()->get('AppBundle\Service\ProcessLocker');
    }


    private function writeLn($line)
    {
        $this->cmdUtil->writelnWithTimestamp($line);
    }
}
