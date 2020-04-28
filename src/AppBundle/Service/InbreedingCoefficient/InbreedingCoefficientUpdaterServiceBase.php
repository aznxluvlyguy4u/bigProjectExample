<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\CalcIcAscendantPath;
use AppBundle\Entity\CalcIcAscendantPath2;
use AppBundle\Entity\CalcIcAscendantPath3;
use AppBundle\Entity\CalcIcAscendantPathRepositoryInterface;
use AppBundle\Entity\CalcIcLoop;
use AppBundle\Entity\CalcIcLoop2;
use AppBundle\Entity\CalcIcLoop3;
use AppBundle\Entity\CalcIcLoopRepositoryInterface;
use AppBundle\Entity\CalcIcParent;
use AppBundle\Entity\CalcIcParent2;
use AppBundle\Entity\CalcIcParent3;
use AppBundle\Entity\CalcIcParentDetails;
use AppBundle\Entity\CalcIcParentDetails2;
use AppBundle\Entity\CalcIcParentDetails3;
use AppBundle\Entity\CalcIcParentDetailsRepositoryInterface;
use AppBundle\Entity\CalcIcParentRepositoryInterface;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\InbreedingCoefficient;
use AppBundle\Entity\InbreedingCoefficientProcess;
use AppBundle\Entity\InbreedingCoefficientProcessRepository;
use AppBundle\Entity\InbreedingCoefficientRepository;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\Exception\Sqs\InbreedingCoefficientProcessException;
use AppBundle\model\ParentIdsPair;
use AppBundle\model\process\ProcessDetails;
use AppBundle\Setting\InbreedingCoefficientSetting;
use AppBundle\Util\LoggerUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class InbreedingCoefficientUpdaterServiceBase
{
    const LOG_LOOPS_ON_NEW_LINE = false;

    const PARENTS_ACTION_NEW = 'NEW';
    const PARENTS_ACTION_UPDATE = 'UPD';
    const PARENTS_ACTION_EMPTY = '---';

    /** @var string */
    protected $processSlot;

    /** @var EntityManagerInterface */
    protected $em;
    /** @var LoggerInterface */
    protected $logger;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var boolean */
    protected $logExtraDetailsForDevelopment;

    /** @var CalcIcParentRepositoryInterface */
    protected $calcInbreedingCoefficientParentRepository;
    /** @var CalcIcParentDetailsRepositoryInterface */
    protected $calcInbreedingCoefficientParentDetailsRepository;
    /** @var CalcIcAscendantPathRepositoryInterface */
    protected $calcInbreedingCoefficientAscendantPathRepository;
    /** @var CalcIcLoopRepositoryInterface */
    protected $calcInbreedingCoefficientLoopRepository;

    /** @var InbreedingCoefficientRepository */
    protected $inbreedingCoefficientRepository;

    /** @var int */
    protected $updateCount = 0;
    /** @var int */
    protected $newCount = 0;
    /** @var int */
    protected $skipped = 0;
    /** @var int */
    protected $batchCount = 0;

    /** @var string */
    protected $logMessageGroup;
    /** @var string */
    protected $logMessageParents;
    /** @var string */
    protected $logMessageParentsAction;
    /** @var int */
    protected $processedInbreedingCoefficientPairs;
    /** @var int */
    protected $totalInbreedingCoefficientPairs;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        ?bool $logExtraDetailsForDevelopment = false
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->logExtraDetailsForDevelopment = $logExtraDetailsForDevelopment;

        $this->inbreedingCoefficientRepository = $this->em->getRepository(InbreedingCoefficient::class);

        // Set default process slot value
        $this->setProcessSlot(InbreedingCoefficientProcessSlot::ADMIN);
    }


    public function displayAll()
    {
        $lockedSlots = array_map(
            function (InbreedingCoefficientProcess $process) {
                return $process->getSlotName();
            },
            $this->processRepository()->getLockedProcesses()
        );

        $count = empty($lockedSlots) ? 0 : count($lockedSlots);
        $list = empty($lockedSlots) ? '' : ': '.implode(',', $lockedSlots);

        $this->logger->notice("$count inbreeding coefficient process slots are locked$list");
    }

    public function unlockAll()
    {
        $this->displayAll();
        $this->em->getRepository(InbreedingCoefficientProcess::class)->unlockAllProcesses();
    }


    /**
     * Purge queue before running this
     *
     * @param  InbreedingCoefficientProcess  $process
     * @return bool
     */
    protected function cancelBase(InbreedingCoefficientProcess $process): bool
    {
        if ($process->getFinishedAt() == null) {
            $process->setFinishedAt(new \DateTime());
            $process->setIsCancelled(true);

            $this->em->persist($process);
            $this->em->flush();
            return true;
        }
        return false;
    }


    protected function setProcessSlot(string $processSlot)
    {
        $this->processSlot = $processSlot;
        switch ($this->processSlot) {
            case InbreedingCoefficientProcessSlot::ADMIN:
                $this->calcInbreedingCoefficientParentRepository = $this->em->getRepository(CalcIcParent::class);
                $this->calcInbreedingCoefficientParentDetailsRepository = $this->em->getRepository(CalcIcParentDetails::class);
                $this->calcInbreedingCoefficientAscendantPathRepository = $this->em->getRepository(CalcIcAscendantPath::class);
                $this->calcInbreedingCoefficientLoopRepository = $this->em->getRepository(CalcIcLoop::class);
                break;
            case InbreedingCoefficientProcessSlot::REPORT:
                $this->calcInbreedingCoefficientParentRepository = $this->em->getRepository(CalcIcParent2::class);
                $this->calcInbreedingCoefficientParentDetailsRepository = $this->em->getRepository(CalcIcParentDetails2::class);
                $this->calcInbreedingCoefficientAscendantPathRepository = $this->em->getRepository(CalcIcAscendantPath2::class);
                $this->calcInbreedingCoefficientLoopRepository = $this->em->getRepository(CalcIcLoop2::class);
                break;
            case InbreedingCoefficientProcessSlot::SMALL:
            default:
                $this->calcInbreedingCoefficientParentRepository = $this->em->getRepository(CalcIcParent3::class);
                $this->calcInbreedingCoefficientParentDetailsRepository = $this->em->getRepository(CalcIcParentDetails3::class);
                $this->calcInbreedingCoefficientAscendantPathRepository = $this->em->getRepository(CalcIcAscendantPath3::class);
                $this->calcInbreedingCoefficientLoopRepository = $this->em->getRepository(CalcIcLoop3::class);
                break;
        }
    }


    protected function processRepository(): InbreedingCoefficientProcessRepository
    {
        return $this->em->getRepository(InbreedingCoefficientProcess::class);
    }


    protected function resetCounts(?InbreedingCoefficientProcess $process = null)
    {
        $this->newCount = ($process ? $process->getNewCount() : 0);
        $this->updateCount = ($process ? $process->getUpdatedCount() : 0);
        $this->skipped = ($process ? $process->getSkippedCount() : 0);
        $this->batchCount = 0;
        $this->logMessageGroup = '';
        $this->logMessageParents = '';
        $this->logMessageParentsAction = '';
        $this->processedInbreedingCoefficientPairs = ($process ? $process->getProcessed() : 0);
        $this->totalInbreedingCoefficientPairs = ($process ? $process->getTotal() : 0);
    }

    protected function getProcessDetails(): ProcessDetails
    {
        return (new ProcessDetails())
            ->setTotal($this->totalInbreedingCoefficientPairs)
            ->setProcessed($this->processedInbreedingCoefficientPairs)
            ->setNew($this->newCount)
            ->setSkipped($this->skipped)
            ->setUpdated($this->updateCount)
            ->setLogMessage($this->logMessage())
        ;
    }

    private function writeBatchCountInnerLoop(string $suffix = '')
    {
        if ($this->logExtraDetailsForDevelopment) {
            $this->writeBatchCount($suffix);
        }
    }

    protected function writeBatchCount(string $suffix = '')
    {
        $message = $this->logMessage($suffix);
        if ($this->processedInbreedingCoefficientPairs <= 1 || self::LOG_LOOPS_ON_NEW_LINE) {
            $this->logger->notice($message);
        } else {
            LoggerUtil::overwriteNoticeLoggerInterface($this->logger, $message);
        }
        $message = null;
    }

    protected function logMessage(string $suffix = ''): string
    {
        $processedCount = $this->processedInbreedingCoefficientPairs;

        $memoryUsage = LoggerUtil::getMemoryUsageInMb()."Mb";
        if (!empty($this->totalInbreedingCoefficientPairs)) {
            $percentage = round(
                $this->processedInbreedingCoefficientPairs / $this->totalInbreedingCoefficientPairs * 100,
                0
            );
            $progressOverview = "($percentage% - $processedCount/".$this->totalInbreedingCoefficientPairs." - $memoryUsage)";
        } else {
            $progressOverview = "total $processedCount - $memoryUsage";
        }

        return "InbreedingCoefficient records $progressOverview: "
            .$this->newCount.'|'.$this->updateCount." [new|updated]"
            .' | '.$this->logMessageGroup
            .' | '.$this->logMessageParents
            .(empty($this->logMessageParentsAction) ? '' : '['.$this->logMessageParentsAction.']')
            .(empty($suffix) ? '' : ' | '.$suffix);
    }


    protected function validateLockedDuration(InbreedingCoefficientProcess $process)
    {
        if (!$process->isLocked()) {
            return;
        }

        $lastUpdatedDate = $process->getBumpedAt() ? $process->getBumpedAt() : $process->getStartedAt();
        $loopDurationHours = abs(TimeUtil::durationInHours($lastUpdatedDate, new \DateTime()));
        $maxLimit = InbreedingCoefficientSetting::LOOP_MAX_DURATION_IN_HOURS;
        if ($loopDurationHours > $maxLimit) {
            $slotName = $process->getSlotName();
            throw new InbreedingCoefficientProcessException(
                "Loop duration for InbreedingCoefficientProcess slot $slotName exceeds $maxLimit hours. "
                ."Check if the process is stuck or not."
            );
        }
    }


    protected function clearParentsCalculationTables()
    {
        $this->calcInbreedingCoefficientParentRepository->clearTable($this->logger);
        $this->calcInbreedingCoefficientParentDetailsRepository->clearTable($this->logger);
        $this->calcInbreedingCoefficientAscendantPathRepository->clearTable($this->logger);
    }


    /**
     * @param array $groupedAnimalIdsSets
     */
    protected function refillParentsCalculationTables(array $groupedAnimalIdsSets)
    {
        $this->clearParentsCalculationTables();

        $parentIdsPairs = array_map(function (array $groupedAnimalIdsSet) {
            return new ParentIdsPair(
                $groupedAnimalIdsSet['father_id'],
                $groupedAnimalIdsSet['mother_id']
            );
        }, $groupedAnimalIdsSets);

        $this->calcInbreedingCoefficientParentRepository->fillByParentPairs($parentIdsPairs);
        $this->calcInbreedingCoefficientParentDetailsRepository->fill($this->logger);
        $this->calcInbreedingCoefficientAscendantPathRepository->fill($this->logger);
    }


    protected function processGroupedAnimalIdsSet(array $groupedAnimalIdsSet, bool $recalculate)
    {
        $motherId = $groupedAnimalIdsSet['mother_id'];
        $fatherId = $groupedAnimalIdsSet['father_id'];
        $animalIdsArrayString = $groupedAnimalIdsSet['animal_ids'];
        $litterIdsArrayString = $groupedAnimalIdsSet['litter_ids'];

        $this->logMessageParentsAction = '';
        $this->logMessageParents = "dad: $fatherId, mom: $fatherId";

        $this->writeBatchCountInnerLoop();

        $isSkipped = $this->upsertInbreedingCoefficientForPair($fatherId, $motherId, $recalculate);

        if (!$isSkipped) {
            $animalIds = SqlUtil::getArrayFromPostgreSqlArrayString($animalIdsArrayString);
            $litterIds = SqlUtil::getArrayFromPostgreSqlArrayString($litterIdsArrayString);
            $this->inbreedingCoefficientRepository->matchAnimalsAndLitters($animalIds, $litterIds);
        }

        $this->writeBatchCountInnerLoop();

        $this->processedInbreedingCoefficientPairs++;
    }


    private function upsertInbreedingCoefficientForPair(
        int $fatherId,
        int $motherId,
        bool $recalculate = false,
        bool $setFindGlobalMatch = false
    ) {
        $createNew = false;
        $updateExisting = false;

        $pairExists = $this->inbreedingCoefficientRepository->exists($fatherId, $motherId);

        if ($pairExists) {
            if ($recalculate || $setFindGlobalMatch) {
                $updateExisting = true;
            }
        } else {
            $createNew = true;
        }


        if ($updateExisting) {
            $createNew = $this->updateIfExistsInbreedingCoefficientRecord(
                $fatherId, $motherId, $setFindGlobalMatch, $recalculate
            );
        }

        if ($createNew) {
            $this->createNewInbreedingCoefficientRecord($fatherId, $motherId, $setFindGlobalMatch);
        }

        $isSkipped = !$updateExisting && !$createNew;

        if ($isSkipped) {
            $this->skipped++;
            $this->logMessageParentsAction = self::PARENTS_ACTION_EMPTY;
        }

        return $isSkipped;
    }


    private function updateIfExistsInbreedingCoefficientRecord(
        int $fatherId, int $motherId, bool $setFindGlobalMatch,
        bool $recalculate
    ): bool
    {
        $inbreedingCoefficient = $this->inbreedingCoefficientRepository->findByParentIds($fatherId, $motherId);

        $createNew = false;

        if ($inbreedingCoefficient) {

            if ($recalculate) {
                $value = $this->calculateInbreedingCoefficientValue($fatherId ,$motherId);
                // only update if values have changed
                if (!$inbreedingCoefficient->equalsPrimaryVariableValues($value)) {
                    $inbreedingCoefficient
                        ->setValue($value)
                        ->setFindGlobalMatches($setFindGlobalMatch)
                        ->refreshUpdatedAt();
                    $this->em->persist($inbreedingCoefficient);
                    $this->em->flush();
                    $this->em->clear();

                    $this->updateCount++;

                    $this->logMessageParentsAction = self::PARENTS_ACTION_UPDATE;
                }
            } else if ($inbreedingCoefficient->isFindGlobalMatches() !== $setFindGlobalMatch) {
                $inbreedingCoefficient
                    ->setFindGlobalMatches($setFindGlobalMatch);
                $this->em->persist($inbreedingCoefficient);
                $this->em->flush();
                $this->em->clear();

                $this->updateCount++;
            }
        } else {
            $createNew = true;
        }

        $this->batchCount++;
        return $createNew;
    }


    private function createNewInbreedingCoefficientRecord(int $fatherId, int $motherId, bool $setFindGlobalMatch)
    {
        $value = $this->calculateInbreedingCoefficientValue($fatherId ,$motherId);

        /** @var Ram $ramByReference */
        $ramByReference = $this->em->getReference(Ram::class, $fatherId);
        /** @var Ewe $eweByReference */
        $eweByReference = $this->em->getReference(Ewe::class, $motherId);

        $inbreedingCoefficient = (new InbreedingCoefficient())
            ->setPair($ramByReference, $eweByReference)
            ->setValue($value)
            ->setFindGlobalMatches($setFindGlobalMatch)
        ;
        $this->em->persist($inbreedingCoefficient);
        $this->em->flush();
        $this->em->clear();

        $this->newCount++;
        $this->batchCount++;

        $this->logMessageParentsAction = self::PARENTS_ACTION_NEW;
    }


    private function calculateInbreedingCoefficientValue(int $fatherId, int $motherId)
    {
        $this->calcInbreedingCoefficientLoopRepository->clearTable($this->logger);

        $this->calcInbreedingCoefficientLoopRepository->fill($fatherId, $motherId, $this->logger);
        $inbreedingCoefficientValue = $this->calcInbreedingCoefficientLoopRepository
            ->calculateInbreedingCoefficientFromLoopsAndParentDetails();

        $this->calcInbreedingCoefficientLoopRepository->clearTable($this->logger);
        return $inbreedingCoefficientValue;
    }





    /**
     * @param  array|ParentIdsPair[] $parentIdsPairs
     * @param  bool $recalculate
     * @return array
     */
    protected function getParentGroupedAnimalIdsByPairs(array $parentIdsPairs, bool $recalculate = false): array
    {
        $filter = SqlUtil::getParentIdsFilterFromParentIdsPairs($parentIdsPairs).' AND ';
        return $this->getParentGroupedAnimalAndLitterIds($filter, $recalculate);
    }


    protected function getParentGroupedAnimalAndLitterIds(string $filter, bool $recalculate = false): array
    {
        $recalculateFilter = $recalculate ? '' : 'a.inbreeding_coefficient_match_updated_at ISNULL AND';
        $sql = "SELECT
                    --v.pair_id,
                    MAX(v.father_id) as father_id,
                    MAX(v.mother_id) as mother_id,
                    COALESCE(array_agg(DISTINCT v.animal_id) FILTER ( WHERE v.animal_id NOTNULL ), '{}'::int[])  as animal_ids,
                    COALESCE(array_agg(DISTINCT v.litter_id) FILTER ( WHERE v.litter_id NOTNULL ), '{}'::int[])  as litter_ids
                FROM (
                    SELECT
                        a.id as animal_id,
                        l.id as litter_id,
                        concat(parent_father_id,'-',parent_mother_id) as pair_id,
                        parent_mother_id as mother_id,
                        parent_father_id as father_id
                    FROM animal a
                        LEFT JOIN litter l ON a.litter_id = l.id
                        -- DO NOT DO THE FOLLOWING JOIN!
                        -- Because it will include the same parent pair multiple times during multiple loops
                        -- LEFT JOIN litter l ON a.parent_mother_id = l.animal_mother_id AND a.parent_father_id = l.animal_father_id
                    WHERE $filter
                      $recalculateFilter
                      parent_mother_id NOTNULL AND parent_father_id NOTNULL
                     )v
                GROUP BY v.pair_id";

        return $this->em->getConnection()->query($sql)->fetchAll();
    }

}

