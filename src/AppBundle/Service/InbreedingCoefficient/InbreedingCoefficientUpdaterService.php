<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\Ewe;
use AppBundle\Entity\InbreedingCoefficient;
use AppBundle\Entity\InbreedingCoefficientRepository;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\PedigreeMasterKey;
use AppBundle\model\ParentIdsPair;
use AppBundle\Setting\InbreedingCoefficientSetting;
use AppBundle\Util\InbreedingCoefficientOffspring;
use AppBundle\Util\LoggerUtil;
use AppBundle\Util\PedigreeUtil;
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


    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;

        $this->inbreedingCoefficientRepository = $this->em->getRepository(InbreedingCoefficient::class);
    }


    public static function getParentPairs() {
        // TODO entities or just ids?
    }

    private function resetCounts() {
        $this->newCount = 0;
        $this->updateCount = 0;
        $this->batchCount = 0;
    }

    /**
     * @param array|ParentIdsPair[] $parentIdsPairs
     * @param bool $recalculate
     * @param bool $findGlobalMatch
     */
    private function generateInbreedingCoefficientBase(
        array $parentIdsPairs,
        bool $recalculate = false,
        bool $findGlobalMatch = false
    ) {
        $this->resetCounts();

        if (empty($parentIdsPairs)) {
            $this->logger->debug('No parentIdsPairs found, thus no inbreedcoefficients processed');
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
        $this->em->flush();
        $this->writeBatchCount();
        $this->resetCounts();
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
            if ($recalculate) {
                $updateExisting = true;
            }
        } else {
            $createNew = true;
        }


        if ($updateExisting) {
            $value = $this->getInbreedingCoefficientValue($ramId, $eweId);

            $inbreedingCoefficient = $this->inbreedingCoefficientRepository->findByPair($ramId, $eweId);
            if ($inbreedingCoefficient) {
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

    public function matchAnimals($animalIds) {
        // TODO apply animalIds filter

        // TODO Apply LIMIT and process per batch like for BreedValues

        $sql = "UPDATE animal SET
                  inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                  inbreeding_coefficient_match_updated_at = NOW()
                FROM (
                         SELECT
                             animal.id,
                             ic.id
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
                ) as v(animal_id, inbreeding_coefficient_id)
                WHERE animal.id = v.animal_id;";
    }

    public function matchLitters($litterIds) {
        // TODO apply $litterIds filter

        // TODO Apply LIMIT and process per batch like for BreedValues

        $sql = "UPDATE litter SET
                  inbreeding_coefficient_id = v.inbreeding_coefficient_id,
                  inbreeding_coefficient_match_updated_at = NOW()
                FROM (
                         SELECT
                             litter.id,
                             ic.id
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
                ) as v(litter_id, inbreeding_coefficient_id)
                WHERE litter.id = v.litter_id;";
    }

    public function matchAnimalsGlobal() {
        // TODO
    }

    public function rematchLittersGlobal() {
        // TODO
    }

    public function generateForAllAnimals() {
        // TODO
    }

    public function generateForAllLitters() {
        // TODO
    }
}