<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\Ewe;
use AppBundle\Entity\InbreedingCoefficient;
use AppBundle\Entity\InbreedingCoefficientRepository;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\PedigreeMasterKey;
use AppBundle\model\ParentIdsPair;
use AppBundle\Setting\InbreedingCoefficientSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\InbreedingCoefficientOffspring;
use AppBundle\Util\LoggerUtil;
use AppBundle\Util\PedigreeUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InbreedingCoefficientUpdaterService
{
    private const BATCH_SIZE = 25;

    /** @var EntityManagerInterface */
    private $em;
    /** @var LoggerInterface */
    private $logger;
    /** @var InbreedingCoefficientRepository */
    private $inbreedingCoefficientRepository;

    /** @var int */
    private $updateCount = 0;
    /** @var int */
    private $newCount = 0;
    /** @var int */
    private $batchCount = 0;

    /** @var int */
    private $matchAnimalCount = 0;
    /** @var int */
    private $matchLitterCount = 0;


    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;

        $this->inbreedingCoefficientRepository = $this->em->getRepository(InbreedingCoefficient::class);
    }


    private function resetCounts() {
        $this->newCount = 0;
        $this->updateCount = 0;
        $this->batchCount = 0;
        $this->matchAnimalCount = 0;
        $this->matchLitterCount = 0;
    }

    /**
     * @param array|ParentIdsPair[] $parentIdsPairs
     * @param bool $recalculate
     * @param bool $findGlobalMatch
     * @param bool $isPartOfParentLoop
     */
    private function generateInbreedingCoefficientBase(
        array $parentIdsPairs,
        bool $recalculate = false,
        bool $findGlobalMatch = false,
        bool $isPartOfParentLoop = false
    ) {
        if (!$isPartOfParentLoop) {
            $this->resetCounts();
        }

        if (empty($parentIdsPairs)) {
            $this->logger->debug('No parentIdsPairs found, thus no inbreeding coefficients processed');
            return;
        }

        foreach ($parentIdsPairs as $parentIdsPair) {
            $this->generateInbreedingCoefficientForPair(
                $parentIdsPair,
                $recalculate,
                $findGlobalMatch
            );

            if ($this->batchCount >= self::BATCH_SIZE) {
                $this->em->flush();
                $this->batchCount = 0;
                $this->writeBatchCount();
            }
        }
//        dump($this->em->getUnitOfWork()->getScheduledEntityInsertions());
        $this->em->flush();
        $this->writeBatchCount();

        if (!$isPartOfParentLoop) {
            $this->resetCounts();
        }
    }

    private function writeBatchCount() {
        $totalCount = $this->updateCount + $this->newCount;
        $message = 'InbreedingCoefficient records: new '.$this->newCount.', updated '.$this->updateCount. ', total '.$totalCount;
        if ($totalCount == 0) {
            $this->logger->debug($message);
        } else {
            LoggerUtil::overwriteDebugLoggerInterface($this->logger, $message);
        }
    }

    private function generateInbreedingCoefficientForPair(
        ParentIdsPair $parentIdsPairs,
        bool $recalculate = false,
        bool $findGlobalMatch = false
    ) {
        $ramId = $parentIdsPairs->getRamId();
        $eweId = $parentIdsPairs->getEweId();

        $createNew = false;
        $updateExisting = false;

        $pairExists = $this->inbreedingCoefficientRepository->exists($ramId, $eweId);

        if ($pairExists) {
            if ($recalculate || $findGlobalMatch) {
                $updateExisting = true;
            }
        } else {
            $createNew = true;
        }


        if ($updateExisting) {
            $inbreedingCoefficient = $this->inbreedingCoefficientRepository->findByPair($parentIdsPairs);

            if ($inbreedingCoefficient) {

                if ($recalculate) {
                    $value = $this->getInbreedingCoefficientValue($ramId, $eweId);
                    // only update if values have changed
                    if (!$inbreedingCoefficient->equalsPrimaryVariableValues(
                        $findGlobalMatch,
                        $value
                    )) {
                        $inbreedingCoefficient
                            ->setValue($value)
                            ->setFindGlobalMatches($findGlobalMatch)
                            ->refreshUpdatedAt();
                        $this->em->persist($inbreedingCoefficient);
                    }
                } else {
                    $inbreedingCoefficient
                        ->setFindGlobalMatches($findGlobalMatch);
                    $this->em->persist($inbreedingCoefficient);
                }

            } else {
                $createNew = true;
            }

            $this->updateCount++;
            $this->batchCount++;
        }


        if ($createNew) {
            $value = $this->getInbreedingCoefficientValue($ramId, $eweId);
            /** @var Ram $ramByReference */
            $ramByReference = $this->em->getReference(Ram::class, $ramId);
            /** @var Ewe $eweByReference */
            $eweByReference = $this->em->getReference(Ewe::class, $eweId);

            $inbreedingCoefficient = (new InbreedingCoefficient())
                ->setPair($ramByReference, $eweByReference)
                ->setValue($value)
                ->setFindGlobalMatches($findGlobalMatch)
            ;
            $this->em->persist($inbreedingCoefficient);

            $this->newCount++;
            $this->batchCount++;
        }
    }

    private function getInbreedingCoefficientValue(int $ramId, int $eweId,
                                                   int $generationOfAscendants = InbreedingCoefficientSetting::DEFAULT_GENERATION_OF_ASCENDANTS
    ): float {
        $ramData = ['id' => $ramId];
        $ewesData = ['id' => $eweId];

        $parentIds = [
            $ramId => $ramId,
            $eweId => $eweId,
        ];

        $parentSearchArray = [];
        $childrenSearchArray = [];

        $parentAscendants = PedigreeUtil::findNestedParentsBySingleSqlQuery($this->em->getConnection(), $parentIds, $generationOfAscendants,PedigreeMasterKey::ULN);

        $inbreedingCoefficientResult = new InbreedingCoefficientOffspring($this->em,
            $ramData, $ewesData, $parentSearchArray, $childrenSearchArray, $parentAscendants);

        return $inbreedingCoefficientResult->getValue();
    }

    /**
     * @param array|ParentIdsPair[] $parentIdsPairs
     * @param bool $findGlobalMatch
     */
    public function generateInbreedingCoefficients(array $parentIdsPairs, bool $findGlobalMatch = false) {
        $this->generateInbreedingCoefficientBase($parentIdsPairs, false, $findGlobalMatch);
    }

    /**
     * @param array|ParentIdsPair[] $parentIdsPairs
     * @param bool $findGlobalMatch
     */
    public function regenerateInbreedingCoefficients(array $parentIdsPairs, bool $findGlobalMatch = false) {
        $this->generateInbreedingCoefficientBase($parentIdsPairs, true, $findGlobalMatch);
    }

    public function matchAnimalsAndLitters($animalIds = [], $litterIds = []) {

        if (empty($animalIds) && empty($litterIds)) {
            return;
        }

        $this->resetCounts();

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

            if (!empty($animalSelectResult)) {
                $valuesString = SqlUtil::valueStringFromNestedArray($animalSelectResult, false);

                $updateAnimalSql = "UPDATE animal SET
                  inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                  inbreeding_coefficient_match_updated_at = NOW()
                FROM (
                         VALUES $valuesString
                ) as v(animal_id, inbreeding_coefficient_id)
                WHERE animal.id = v.animal_id";
                $animalUpdatedCount = SqlUtil::updateWithCount($this->em->getConnection(), $updateAnimalSql);
                $this->matchAnimalCount += $animalUpdatedCount;
            }
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

            if (!empty($litterSelectResult)) {
                $valuesString = SqlUtil::valueStringFromNestedArray($litterSelectResult, false);

                $updateLitterSql = "UPDATE litter SET
                  inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                  inbreeding_coefficient_match_updated_at = NOW()
                FROM (
                         VALUES $valuesString
                ) as v(litter_id, inbreeding_coefficient_id)
                WHERE litter.id = v.litter_id";

                $litterUpdatedCount = SqlUtil::updateWithCount($this->em->getConnection(), $updateLitterSql);
                $this->matchLitterCount += $litterUpdatedCount;
            }
        }

        $this->logger->debug("Animals matched with inbreeding coefficient: "
            .$this->matchAnimalCount.' animals, '
            .$this->matchLitterCount.' litters, '
            .($this->matchAnimalCount + $this->matchLitterCount).' total'
        );

        $this->resetCounts();
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

            if (!empty($animalSelectResult)) {
                $valuesString = SqlUtil::valueStringFromNestedArray($animalSelectResult, false);

                $updateAnimalSql = "UPDATE animal SET
                  inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                  inbreeding_coefficient_match_updated_at = NOW()
                FROM (
                         VALUES $valuesString
                ) as v(animal_id, inbreeding_coefficient_id)
                WHERE animal.id = v.animal_id";
                $animalUpdatedCount = SqlUtil::updateWithCount($this->em->getConnection(), $updateAnimalSql);
                $updatedCount += $animalUpdatedCount;
                $this->matchAnimalCount += $animalUpdatedCount;
            }

            if (!empty($litterSelectResult)) {
                $valuesString = SqlUtil::valueStringFromNestedArray($litterSelectResult, false);

                $updateLitterSql = "UPDATE litter SET
                      inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                      inbreeding_coefficient_match_updated_at = NOW()
                    FROM (
                             VALUES $valuesString
                    ) as v(litter_id, inbreeding_coefficient_id)
                    WHERE litter.id = v.litter_id";

                $litterUpdatedCount = SqlUtil::updateWithCount($this->em->getConnection(), $updateLitterSql);
                $updatedCount += $litterUpdatedCount;
                $this->matchLitterCount += $litterUpdatedCount;
            }

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
        $this->em->getConnection()->query($updateInbreedingCoefficientSql)->execute();
    }

    public function generateForAllAnimalsAndLitters() {
        $this->generateForAllAnimalsAndLittersBase(false);
    }

    public function regenerateForAllAnimalsAndLitters() {
        $this->generateForAllAnimalsAndLittersBase(true);
    }

    private function generateForAllAnimalsAndLittersBase(bool $recalculate) {
        if ($recalculate) {
            $this->inbreedingCoefficientRepository->clearMatchUpdatedAt();
        }

        $this->resetCounts();
        do {
            $parentIdsPairs = $this->inbreedingCoefficientRepository->findParentIdsPairsWithMissingInbreedingCoefficient(self::BATCH_SIZE, $recalculate);
            $this->generateInbreedingCoefficientBase($parentIdsPairs, $recalculate,true, true);

            $this->matchAnimalsAndLittersGlobal();

        } while(!empty($parentIdsPairs));
        $this->writeBatchCount();
        $this->resetCounts();
    }
}
