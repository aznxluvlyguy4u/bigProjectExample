<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\CalcIcAscendantPath;
use AppBundle\Entity\CalcIcAscendantPath2;
use AppBundle\Entity\CalcIcAscendantPathRepositoryInterface;
use AppBundle\Entity\CalcIcLoop;
use AppBundle\Entity\CalcIcLoop2;
use AppBundle\Entity\CalcIcLoopRepositoryInterface;
use AppBundle\Entity\CalcIcParent;
use AppBundle\Entity\CalcIcParent2;
use AppBundle\Entity\CalcIcParentDetails;
use AppBundle\Entity\CalcIcParentDetails2;
use AppBundle\Entity\CalcIcParentDetailsRepositoryInterface;
use AppBundle\Entity\CalcIcParentRepositoryInterface;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\InbreedingCoefficient;
use AppBundle\Entity\InbreedingCoefficientRepository;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\model\metadata\YearMonthData;
use AppBundle\model\ParentIdsPair;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\LoggerUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InbreedingCoefficientUpdaterServiceBase
{
    const LOG_LOOPS_ON_NEW_LINE = false;

    const PARENTS_ACTION_NEW = 'NEW';
    const PARENTS_ACTION_UPDATE = 'UPD';
    const PARENTS_ACTION_EMPTY = '---';

    /** @var string */
    protected $processSlot;

    /** @var EntityManagerInterface */
    private $em;
    /** @var LoggerInterface */
    private $logger;
    /** @var boolean */
    private $logExtraDetailsForDevelopment;

    /** @var CalcIcParentRepositoryInterface */
    private $calcInbreedingCoefficientParentRepository;
    /** @var CalcIcParentDetailsRepositoryInterface */
    private $calcInbreedingCoefficientParentDetailsRepository;
    /** @var CalcIcAscendantPathRepositoryInterface */
    private $calcInbreedingCoefficientAscendantPathRepository;
    /** @var CalcIcLoopRepositoryInterface */
    private $calcInbreedingCoefficientLoopRepository;

    /** @var InbreedingCoefficientRepository */
    private $inbreedingCoefficientRepository;

    /** @var int */
    private $updateCount = 0;
    /** @var int */
    private $newCount = 0;
    /** @var int */
    private $skipped = 0;
    /** @var int */
    private $batchCount = 0;

    /** @var int */
    private $matchAnimalCount = 0;
    /** @var int */
    private $matchLitterCount = 0;

    /** @var string */
    private $logMessageGroup;
    /** @var string */
    private $logMessageParents;
    /** @var string */
    private $logMessageParentsAction;
    /** @var int */
    private $processedInbreedingCoefficientPairs;
    /** @var int */
    private $totalInbreedingCoefficientPairs;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        ?bool $logExtraDetailsForDevelopment = false
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->logExtraDetailsForDevelopment = $logExtraDetailsForDevelopment;

        $this->inbreedingCoefficientRepository = $this->em->getRepository(InbreedingCoefficient::class);

        // Set default process slot value
        $this->setProcessSlot(InbreedingCoefficientProcessSlot::_1);
    }


    protected function setProcessSlot(string $processSlot)
    {
        $this->processSlot = $processSlot;
        switch ($this->processSlot) {
            case InbreedingCoefficientProcessSlot::_2:
                $this->calcInbreedingCoefficientParentRepository = $this->em->getRepository(CalcIcParent2::class);
                $this->calcInbreedingCoefficientParentDetailsRepository = $this->em->getRepository(CalcIcParentDetails2::class);
                $this->calcInbreedingCoefficientAscendantPathRepository = $this->em->getRepository(CalcIcAscendantPath2::class);
                $this->calcInbreedingCoefficientLoopRepository = $this->em->getRepository(CalcIcLoop2::class);
                break;
            case InbreedingCoefficientProcessSlot::_1:
            default:
                $this->calcInbreedingCoefficientParentRepository = $this->em->getRepository(CalcIcParent::class);
                $this->calcInbreedingCoefficientParentDetailsRepository = $this->em->getRepository(CalcIcParentDetails::class);
                $this->calcInbreedingCoefficientAscendantPathRepository = $this->em->getRepository(CalcIcAscendantPath::class);
                $this->calcInbreedingCoefficientLoopRepository = $this->em->getRepository(CalcIcLoop::class);
                break;
        }
    }


    private function resetCounts()
    {
        $this->newCount = 0;
        $this->updateCount = 0;
        $this->skipped = 0;
        $this->batchCount = 0;
        $this->matchAnimalCount = 0;
        $this->matchLitterCount = 0;
        $this->logMessageGroup = '';
        $this->logMessageParents = '';
        $this->logMessageParentsAction = '';
        $this->processedInbreedingCoefficientPairs = 0;
        $this->totalInbreedingCoefficientPairs = 0;
    }

    private function writeBatchCountInnerLoop(string $suffix = '')
    {
        if ($this->logExtraDetailsForDevelopment) {
            $this->writeBatchCount($suffix);
        }
    }

    private function writeBatchCount(string $suffix = '')
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

        $message = "InbreedingCoefficient records $progressOverview: "
            .$this->newCount.'|'.$this->updateCount." [new|updated]"
            .' | '.$this->logMessageGroup
            .' | '.$this->logMessageParents
            .(empty($this->logMessageParentsAction) ? '' : '['.$this->logMessageParentsAction.']')
            .(empty($suffix) ? '' : ' | '.$suffix);
        if ($processedCount <= 1 || self::LOG_LOOPS_ON_NEW_LINE) {
            $this->logger->notice($message);
        } else {
            LoggerUtil::overwriteNoticeLoggerInterface($this->logger, $message);
        }
        $message = null;
    }


    protected function matchAnimalsAndLitters($animalIds = [], $litterIds = []) {

        if (empty($animalIds) && empty($litterIds)) {
            return;
        }

        if (!empty($animalIds)) {
            $animalIdsString = SqlUtil::getFilterListString($animalIds, false);
            $animalFilter = " AND id IN ($animalIdsString)";


            $animalSelectSql = "SELECT
                             animal.id as animal_id,
                             ic.id as inbreeding_coefficient_id
                         FROM inbreeding_coefficient ic
                         INNER JOIN (
                             SELECT
                                 id,
                                 parent_mother_id,
                                 parent_father_id
                             FROM animal
                             WHERE parent_father_id NOTNULL AND parent_mother_id NOTNULL
                                      $animalFilter
                         )animal ON animal.parent_father_id = ic.ram_id AND animal.parent_mother_id = ic.ewe_id";
            $animalSelectResult = $this->em->getConnection()->query($animalSelectSql)->fetchAll();

            $animalUpdatedCount = $this->updateAnimalBySelectResult($animalSelectResult);
            $this->matchAnimalCount += $animalUpdatedCount;
        }

        if (!empty($litterIds)) {
            $litterIdsString = SqlUtil::getFilterListString($litterIds, false);
            $litterFilter = " AND id IN ($litterIdsString)";

            $litterSelectSql = "SELECT
                                 litter.id as litter_id,
                                 ic.id as inbreeding_coefficient_id
                             FROM inbreeding_coefficient ic
                             INNER JOIN (
                                 SELECT
                                     id,
                                     animal_father_id,
                                     animal_mother_id
                                 FROM litter
                                 WHERE animal_father_id NOTNULL AND animal_mother_id NOTNULL
                                          $litterFilter
                             )litter ON litter.animal_father_id = ic.ram_id AND litter.animal_mother_id = ic.ewe_id";
            $litterSelectResult = $this->em->getConnection()->query($litterSelectSql)->fetchAll();

            $litterUpdatedCount = $this->updateLitterBySelectResult($litterSelectResult);
            $this->matchLitterCount += $litterUpdatedCount;
        }

        $this->logger->debug("Animals matched with inbreeding coefficient: "
            .$this->matchAnimalCount.' animals, '
            .$this->matchLitterCount.' litters, '
            .($this->matchAnimalCount + $this->matchLitterCount).' total'
        );
    }

    public function matchAnimalsAndLittersGlobal() {
        $this->resetCounts();

        do {

            $updatedCount = 0;

            $animalSelectSql = "SELECT
                             animal.id as animal_id,
                             ic.id as inbreeding_coefficient_id
                         FROM inbreeding_coefficient ic
                         INNER JOIN (
                             SELECT
                                 id,
                                 parent_mother_id,
                                 parent_father_id
                             FROM animal
                             WHERE parent_father_id NOTNULL AND parent_mother_id NOTNULL
                                AND inbreeding_coefficient_match_updated_at ISNULL
                         )animal ON animal.parent_father_id = ic.ram_id AND animal.parent_mother_id = ic.ewe_id
                         WHERE ic.find_global_matches";
            $animalSelectResult = $this->em->getConnection()->query($animalSelectSql)->fetchAll();

            $animalUpdatedCount = $this->updateAnimalBySelectResult($animalSelectResult);
            $updatedCount += $animalUpdatedCount;
            $this->matchAnimalCount += $animalUpdatedCount;


            $litterSelectSql = "SELECT
                                 litter.id as litter_id,
                                 ic.id as inbreeding_coefficient_id
                             FROM inbreeding_coefficient ic
                             INNER JOIN (
                                 SELECT
                                     id,
                                     animal_father_id,
                                     animal_mother_id
                                 FROM litter
                                 WHERE animal_father_id NOTNULL AND animal_mother_id NOTNULL
                                    AND inbreeding_coefficient_match_updated_at ISNULL
                             )litter ON litter.animal_father_id = ic.ram_id AND litter.animal_mother_id = ic.ewe_id
                             WHERE ic.find_global_matches";
            $litterSelectResult = $this->em->getConnection()->query($litterSelectSql)->fetchAll();

            $litterUpdatedCount = $this->updateLitterBySelectResult($litterSelectResult);
            $updatedCount += $litterUpdatedCount;
            $this->matchLitterCount += $litterUpdatedCount;


            $inbreedingCoefficientIdsFromAnimals = array_unique(array_map(function(array $array) {
                return $array['inbreeding_coefficient_id'];
            }, $animalSelectResult));

            $inbreedingCoefficientIdsFromLitters = array_unique(array_map(function(array $array) {
                return $array['inbreeding_coefficient_id'];
            }, $litterSelectResult));

            $inbreedingCoefficientIds = ArrayUtil::concatArrayValues([
                $inbreedingCoefficientIdsFromAnimals, $inbreedingCoefficientIdsFromLitters
            ]);

            if (!empty($inbreedingCoefficientIds)) {
                $inbreedingCoefficientIdValuesString = SqlUtil::getIdsFilterListString($inbreedingCoefficientIds);
                $updateInbreedingCoefficientSql = "UPDATE inbreeding_coefficient SET
              find_global_matches = false,
              updated_at = NOW()
            WHERE inbreeding_coefficient.id IN ($inbreedingCoefficientIdValuesString)";
                $this->em->getConnection()->query($updateInbreedingCoefficientSql)->execute();
            }

        } while($updatedCount > 0);

        $this->logger->debug("Animals matched with inbreeding coefficient: "
            .$this->matchAnimalCount.' animals, '
            .$this->matchLitterCount.' litters, '
            .($this->matchAnimalCount + $this->matchLitterCount).' total'
        );

        $this->resetCounts();

        /*
         * Clear find_global_matched = true for inbreeding_coefficient
         * where animal or litter data was not updated, because it was not needed to be updated
         */
        $updateInbreedingCoefficientSql = "UPDATE inbreeding_coefficient SET
              find_global_matches = false,
              updated_at = NOW()
            WHERE find_global_matches = true";
        $this->em->getConnection()->executeQuery($updateInbreedingCoefficientSql);
    }

    private function updateAnimalBySelectResult(array $animalSelectResult): int {
        if (empty($animalSelectResult)) {
            return 0;
        }

        $valuesString = SqlUtil::valueStringFromNestedArray($animalSelectResult, false);

        $updateAnimalSql = "UPDATE animal SET
                  inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                  inbreeding_coefficient_match_updated_at = NOW()
                FROM (
                         VALUES $valuesString
                ) as v(animal_id, inbreeding_coefficient_id)
                WHERE animal.id = v.animal_id";
        return intval(SqlUtil::updateWithCount($this->em->getConnection(), $updateAnimalSql));
    }

    private function updateLitterBySelectResult(array $litterSelectResult): int {
        if (empty($litterSelectResult)) {
            return 0;
        }

        $valuesString = SqlUtil::valueStringFromNestedArray($litterSelectResult, false);

        $updateLitterSql = "UPDATE litter SET
                      inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                      inbreeding_coefficient_match_updated_at = NOW()
                    FROM (
                             VALUES $valuesString
                    ) as v(litter_id, inbreeding_coefficient_id)
                    WHERE litter.id = v.litter_id";

        return intval(SqlUtil::updateWithCount($this->em->getConnection(), $updateLitterSql));
    }

    protected function generateForAnimalsAndLittersOfUbnBase(string $ubn, bool $recalculate)
    {
        $parentIdsPairs = $this->inbreedingCoefficientRepository->findParentIdsPairsWithMissingInbreedingCoefficient(0, $recalculate, $ubn);
        $this->generateInbreedingCoefficientsBase($parentIdsPairs,false, $recalculate);
    }

    private function clearParentsCalculationTables()
    {
        $this->calcInbreedingCoefficientParentRepository->clearTable($this->logger);
        $this->calcInbreedingCoefficientParentDetailsRepository->clearTable($this->logger);
        $this->calcInbreedingCoefficientAscendantPathRepository->clearTable($this->logger);
    }


    /**
     * @param  array|ParentIdsPair[]  $parentIdsPairs
     * @param  bool  $setFindGlobalMatch
     * @param  bool  $recalculate
     */
    protected function generateInbreedingCoefficientsBase(
        array $parentIdsPairs, bool $setFindGlobalMatch, bool $recalculate
    )
    {
        if (empty($parentIdsPairs)) {
            $this->logger->notice('ParentIdsPairs input is empty. Nothing will be processed');
            return;
        }

        $this->resetCounts();

        $this->clearParentsCalculationTables();

        $this->fillCalcTablesExceptForLoopsForParentIdsPairs($parentIdsPairs);

        $groupedAnimalIdsSets = $this->getParentGroupedAnimalIdsByPairs($parentIdsPairs, $recalculate);
        $setCount = count($groupedAnimalIdsSets);

        $this->totalInbreedingCoefficientPairs = $setCount;
        $this->logMessageGroup = $setCount.' parent groups';

        $this->processGroupedAnimalIdsSets($groupedAnimalIdsSets, $recalculate, $setFindGlobalMatch);

        $this->writeBatchCount('Completed!');

        $this->clearParentsCalculationTables();
    }

    /**
     * @param array|ParentIdsPair[] $parentIdsPairs
     */
    protected function fillCalcTablesExceptForLoopsForParentIdsPairs($parentIdsPairs)
    {
        $this->calcInbreedingCoefficientParentRepository->fillByParentPairs($parentIdsPairs);
        $this->calcInbreedingCoefficientParentDetailsRepository->fill($this->logger);
        $this->calcInbreedingCoefficientAscendantPathRepository->fill($this->logger);
    }


    private function processGroupedAnimalIdsSets(array $groupedAnimalIdsSets, bool $recalculate, bool $setFindGlobalMatch)
    {
        foreach ($groupedAnimalIdsSets as $groupedAnimalIdSet)
        {
            $this->processGroupedAnimalIdsSet($groupedAnimalIdSet, $recalculate, $setFindGlobalMatch);
        }
    }


    private function processGroupedAnimalIdsSet(array $groupedAnimalIdsSet, bool $recalculate, bool $setFindGlobalMatch)
    {
        $motherId = $groupedAnimalIdsSet['mother_id'];
        $fatherId = $groupedAnimalIdsSet['father_id'];
        $animalIdsArrayString = $groupedAnimalIdsSet['animal_ids'];
        $litterIdsArrayString = $groupedAnimalIdsSet['litter_ids'];

        $this->logMessageParentsAction = '';
        $this->logMessageParents = "dad: $fatherId, mom: $fatherId";

        $this->writeBatchCountInnerLoop();

        $isSkipped = $this->upsertInbreedingCoefficientForPair($fatherId, $motherId, $recalculate, $setFindGlobalMatch);

        if (!$isSkipped) {
            $animalIds = SqlUtil::getArrayFromPostgreSqlArrayString($animalIdsArrayString);
            $litterIds = SqlUtil::getArrayFromPostgreSqlArrayString($litterIdsArrayString);
            $this->matchAnimalsAndLitters($animalIds, $litterIds);
        }

        $this->processedInbreedingCoefficientPairs++;
    }


    protected function generateForAllAnimalsAndLitterBase(bool $recalculate, bool $setFindGlobalMatch)
    {
        $this->resetCounts();

        $this->updateAnimalsWithoutParents();
        $this->clearParentsCalculationTables();

        $yearsAndMonthsAnimalIdsSets = $this->calcInbreedingCoefficientParentRepository->getAllYearsAndMonths();

        if ($recalculate) {
            $this->totalInbreedingCoefficientPairs = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );
        } else {
            $this->totalInbreedingCoefficientPairs = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getMissingInbreedingCoefficientCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );

            $alreadyExistsCount = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getNonMissingCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );
            $this->logger->notice("$alreadyExistsCount inbreeding coefficient pairs skipped (already exist). Includes animals without both parents.");

            $yearsAndMonthsAnimalIdsSets = array_filter(
                $yearsAndMonthsAnimalIdsSets, function (YearMonthData $yearMonthData) {
                    return $yearMonthData->hasMissingInbreedingCoefficients();
                }
            );
        }

        foreach ($yearsAndMonthsAnimalIdsSets as $period)
        {
            $this->generateForAllAnimalsAndLitterBasePeriodLoop(
                $period, $recalculate, $setFindGlobalMatch
            );
            gc_collect_cycles();
        }

        $this->writeBatchCount('Completed!');
        $this->clearParentsCalculationTables();
    }


    private function generateForAllAnimalsAndLitterBasePeriodLoop(
        YearMonthData $period, bool $recalculate, bool $setFindGlobalMatch
    )
    {
        $year = $period->getYear();
        $month = $period->getMonth();

        $this->logMessageGroup = "$year-$month (year-month)";

        $groupedAnimalIdsSets = $this->getParentGroupedAnimalIdsByYearAndMonth($year, $month);
        $this->writeBatchCount();

        foreach ($groupedAnimalIdsSets as $groupedAnimalIdsSet)
        {
            $this->generateForAllAnimalsAndLitterBaseGroupedAnimalIdsSetsLoop(
                $groupedAnimalIdsSet, $recalculate, $setFindGlobalMatch
            );
        }

        $groupedAnimalIdsSets = null;
        gc_collect_cycles();
    }


    private function generateForAllAnimalsAndLitterBaseGroupedAnimalIdsSetsLoop(
        array $groupedAnimalIdsSet, bool $recalculate, bool $setFindGlobalMatch
    )
    {
        $this->logMessageParentsAction = '';
        $this->logMessageParents = '';

        $parentIdsPairs = [
            new ParentIdsPair(
                $groupedAnimalIdsSet['father_id'],
                $groupedAnimalIdsSet['mother_id']
            )
        ];

        $this->fillCalcTablesExceptForLoopsForParentIdsPairs($parentIdsPairs);
        $this->processGroupedAnimalIdsSets([$groupedAnimalIdsSet], $recalculate, $setFindGlobalMatch);

        $parentIdsPairs = null;

        $this->writeBatchCountInnerLoop();

        $this->clearParentsCalculationTables();
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


    private function updateAnimalsWithoutParents()
    {
        $this->logger->notice("Remove update mark if animal now has both parents...");
        $sql1 = "UPDATE animal SET inbreeding_coefficient_match_updated_at = NULL
WHERE inbreeding_coefficient_id ISNULL AND inbreeding_coefficient_match_updated_at NOTNULL
    AND parent_father_id NOTNULL AND parent_mother_id NOTNULL";
        $this->em->getConnection()->executeQuery($sql1);

        $this->logger->notice("Add update mark to animals without parents...");
        $sql2 = "UPDATE animal SET inbreeding_coefficient_match_updated_at = NOW()
WHERE EXISTS(
              SELECT
                  a.id
              FROM animal a
              WHERE (parent_father_id ISNULL OR parent_mother_id ISNULL OR date_of_birth ISNULL)
                AND inbreeding_coefficient_match_updated_at ISNULL
                AND a.id = animal.id
          )";
        $this->em->getConnection()->executeQuery($sql2);
        $this->logger->notice("Finished updating update marks for animals without parents");
    }


    private function getParentGroupedAnimalIdsByYearAndMonth(int $year, int $month, bool $recalculate = false): array
    {
        return $this->getParentGroupedAnimalAndLitterIds(
            "date_part('YEAR', date_of_birth) = $year AND date_part('MONTH', date_of_birth) = $month AND",
            $recalculate
        );
    }


    /**
     * @param  array|ParentIdsPair[] $parentIdsPairs
     * @param  bool $recalculate
     * @return array
     */
    private function getParentGroupedAnimalIdsByPairs(array $parentIdsPairs, bool $recalculate = false): array
    {
        $filter = SqlUtil::getParentIdsFilterFromParentIdsPairs($parentIdsPairs).' AND ';
        return $this->getParentGroupedAnimalAndLitterIds($filter, $recalculate);
    }


    private function getParentGroupedAnimalAndLitterIds(string $filter, bool $recalculate = false): array
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

