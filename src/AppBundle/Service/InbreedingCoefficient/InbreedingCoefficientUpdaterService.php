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
     * @param bool $regenerate
     * @param bool $findGlobalMatch
     */
    private function generateInbreedingCoefficientBase(
        array $parentIdsPairs,
        bool $regenerate = false,
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
                $regenerate,
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
        bool $regenerate = false,
        bool $findGlobalMatch = false
    ) {
        $ramId = $parentIdsPairs->getRamId();
        $eweId = $parentIdsPairs->getEweId();

        $createNew = false;
        $updateExisting = false;

        $pairExists = $this->inbreedingCoefficientRepository->exists($ramId, $eweId);

        if ($pairExists) {
            if ($regenerate) {
                $updateExisting = true;
            }
        } else {
            $createNew = true;
        }


        if ($updateExisting) {
            $value = $this->getInbreedingCoefficientValue($ramId, $eweId);

            $inbreedingCoefficient = $this->inbreedingCoefficientRepository->findByPair($ramId, $eweId);
            if ($inbreedingCoefficient) {
                $inbreedingCoefficient
                    ->setValue($value)
                    ->setFindGlobalMatches($findGlobalMatch)
                    ->refreshUpdatedAt();
                $this->em->persist($inbreedingCoefficient);
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
        // TODO
    }

    public function matchLitters($litterIds) {
        // TODO
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