<?php


namespace AppBundle\Service\Task;


use AppBundle\Entity\ProcessStatus;
use AppBundle\Entity\ProcessStatusRepository;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TaskServiceBase
{
    /** @var EntityManagerInterface */
    protected $em;
    /** @var Connection */
    protected $conn;

    /** @var LoggerInterface */
    protected $logger;
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ProcessStatusRepository */
    protected $processStatusRepository;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        TranslatorInterface $translator
    )
    {
        $this->em = $em;
        $this->conn = $this->em->getConnection();

        $this->logger = $logger;
        $this->translator = $translator;

        $this->processStatusRepository = $this->em->getRepository(ProcessStatus::class);
    }

    /**
     * Purge queue before running this
     *
     * @param  ProcessStatus  $process
     * @param  bool  $unlockProcess
     * @return bool
     */
    protected function cancelBase(ProcessStatus $process, bool $unlockProcess): bool
    {
        if ($process->getFinishedAt() == null) {
            $process->setFinishedAt(new \DateTime());
            $process->setIsCancelled(true);

            if ($unlockProcess) {
                $process->setIsLocked(false);
            }

            $this->em->persist($process);
            $this->em->flush();
            return true;
        }
        return false;
    }


    /**
     * @param ProcessStatus $process
     * @param int $loopMaxDurationInHours
     */
    protected function validateLockedDuration(ProcessStatus $process, int $loopMaxDurationInHours)
    {
        if (!$process->isLocked()) {
            return;
        }

        $lastUpdatedDate = $process->getBumpedAt() ? $process->getBumpedAt() : $process->getStartedAt();
        $loopDurationHours = abs(TimeUtil::durationInHours($lastUpdatedDate, new \DateTime()));
        $maxLimit = $loopMaxDurationInHours;
        if ($loopDurationHours > $maxLimit) {
            $name = $process->getName();

            // TODO Send notification to slack

            throw new \Exception(
                "Loop duration for ProcessStatus type $name exceeds $maxLimit hours. "
                ."Check if the process is stuck or not."
            );
        }
    }
}