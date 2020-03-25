<?php


namespace AppBundle\Service;


use AppBundle\Component\LocationHealthMessageBuilder;
use AppBundle\Criteria\ExteriorCriteria;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRegistration;
use AppBundle\Entity\Ram;
use AppBundle\Entity\ScrapieGenotypeSource;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\ScrapieGenotypeType;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Exception\InvalidSwitchCaseException;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\LocationHealthUpdater;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Twig\Error\Error;

class PedigreeDataGenerator
{
    const BATCH_SIZE = 1000;

    /** @var EntityManagerInterface */
    private $em;
    /** @var Logger */
    private $logger;
    /** @var CommandUtil */
    private $cmdUtil;

    /** @var boolean */
    private $overwriteExistingData;
    /** @var Location */
    private $location;
    /** @var boolean */
    private $isAnyValueUpdated;
    /** @var boolean */
    private $isAnimalValueUpdated;
    /** @var int */
    private $inBatchSize;
    /** @var int */
    private $totalUpdateCount;
    /** @var int */
    private $lastFlushedAnimalId;
    /** @var int */
    private $lastCheckedAnimalId;
    /** @var int */
    private $startAnimalId;
    /** @var LocationHealthUpdater */
    private $locationHealthUpdater;

    public function __construct(EntityManagerInterface $em, Logger $logger, LocationHealthUpdater $locationHealthUpdater)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->locationHealthUpdater = $locationHealthUpdater;
    }


    /**
     * This function is intended to be used, right after the declareBirths of a litter.
     *
     * @param Animal[] $animals The animals array should contain the necessary pedigree and parent data.
     * @param Location $location The current location of all given animals. If empty the current location of each animal is used
     * @return Animal[]
     */
    public function generate($animals, $location)
    {
        return $this->generateBase($animals, $location);
    }


    /**
     * Use this function when processing animals from old declare births.
     *
     * @param Animal[] $animals
     * @param CommandUtil $commandUtil
     * @param int $startAnimalId
     * @return Animal[]
     */
    public function generateBreedAndPedigreeData($animals, CommandUtil $commandUtil, $startAnimalId = 1)
    {
        $this->cmdUtil = $commandUtil;
        $this->startAnimalId = $startAnimalId;

        return $this->generateBase(
            $animals,
            null,
            true,
            false
        );
    }


    /**
     * Use this function when processing animals from old declare births.
     *
     * @param Animal[] $animals
     * @param null $location
     * @param CommandUtil $commandUtil
     * @param int $startAnimalId
     * @return Animal[]
     */
    public function generateScrapieGenotypeData($animals, $location = null, CommandUtil $commandUtil, $startAnimalId = 1)
    {
        $this->cmdUtil = $commandUtil;
        $this->startAnimalId = $startAnimalId;

        return $this->generateBase(
            $animals,
            $location,
            false,
            true
        );
    }


    /**
     * @param Animal[] $animals The animals array should contain the necessary pedigree and parent data.
     * @param Location $location The current location of all given animals. If empty the current location of each animal is used.
     * @param boolean $ignoreScrapieGenotypeGeneration
     * @param boolean $ignoreNonScrapieGenotypeGeneration
     * @param boolean $overwriteExistingData
     * @param int $batchSize
     * @return Animal[]
     */
    private function generateBase($animals,
                                  $location = null,
                                  $ignoreScrapieGenotypeGeneration = false,
                                  $ignoreNonScrapieGenotypeGeneration = false,
                                  $overwriteExistingData = false,
                                  $batchSize = self::BATCH_SIZE
    )
    {
        $this->isAnyValueUpdated = false;
        $this->location = $location;
        $this->overwriteExistingData = $overwriteExistingData;
        $this->inBatchSize = 0;
        $this->totalUpdateCount = 0;

        $this->lastFlushedAnimalId = 0;
        $this->lastCheckedAnimalId = $this->startAnimalId ? $this->startAnimalId : 1;

        if ($this->cmdUtil) {
            $this->cmdUtil->setStartTimeAndPrintIt(count($animals), 1);
        }

        try {

            foreach ($animals as $key => $animal) {
                $animals[$key] = $this->generatePedigreeData(
                    $animal,
                    $ignoreScrapieGenotypeGeneration,
                    $ignoreNonScrapieGenotypeGeneration
                );

                $this->lastCheckedAnimalId = $animal->getId();

                if ($this->inBatchSize%$batchSize === 0 && $this->inBatchSize > 0) {
                    $this->flushBatch();
                }

                $this->advanceProgressBar();
            }

            if ($this->isAnyValueUpdated) {
                $this->flushBatch();
            }

            if ($this->cmdUtil) {
                $this->cmdUtil->setEndTimeAndPrintFinalOverview();
            }

        } catch (\Exception $exception) {
            $this->logger->error($exception->getTraceAsString());
            $this->logger->error($exception->getMessage());
        }

        $this->em->getRepository(ScrapieGenotypeSource::class)->clearSearchArrays();
        $this->resetCounters();

        return $animals;
    }


    /**
     * @param boolean $ignoreScrapieGenotypeGeneration
     * @param boolean $ignoreNonScrapieGenotypeGeneration
     * @param Animal $animal
     * @return Animal
     */
    private function generatePedigreeData(Animal $animal, $ignoreScrapieGenotypeGeneration, $ignoreNonScrapieGenotypeGeneration)
    {
        $this->isAnimalValueUpdated = false;

        // NOTE! Run these functions in this order!
        if (!$ignoreNonScrapieGenotypeGeneration) {
            $animal = $this->generateMissingBreedCodes($animal);
            $animal = $this->generatePedigreeCountryCodeAndNumberAndPedigreeRegister($animal);
            $animal = $this->generateBreedType($animal);
            $animal = $this->matchMissingPedigreeRegisterByBreederNumberInStn($animal);
        }

        if (!$ignoreScrapieGenotypeGeneration) {
            $animal = $this->generateScrapieGenotype($animal);
        }

        if ($this->isAnimalValueUpdated) {
            $this->em->persist($animal);
            $this->inBatchSize++;
        }

        return $animal;
    }


    private function flushBatch()
    {
        $this->em->flush();
        $this->totalUpdateCount += $this->inBatchSize;
        $this->inBatchSize = 0;
        $this->isAnyValueUpdated = false;
        $this->lastFlushedAnimalId = $this->lastCheckedAnimalId;
    }


    private function advanceProgressBar()
    {
        if ($this->cmdUtil) {
            $this->cmdUtil->advanceProgressBar(
                1,
                'inBatch|TotalUpdateCount: '.$this->inBatchSize.'|'.$this->totalUpdateCount
                .'  lastAnimalId(checked|flushed): '.$this->lastCheckedAnimalId.'|'.$this->lastFlushedAnimalId
                );
        }
    }


    private function valueWasUpdated()
    {
        $this->isAnimalValueUpdated = true;
        $this->isAnyValueUpdated = true;
    }


    /**
     * @param Animal $animal
     * @return Location
     */
    private function getLocation(Animal $animal)
    {
        return $this->location ? $this->location : $animal->getLocation();
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function generatePedigreeCountryCodeAndNumberAndPedigreeRegister(Animal $animal)
    {
        if($animal->getPedigreeCountryCode() !== null && $animal->getPedigreeNumber() !== null
        && !$this->overwriteExistingData) {
            return $animal;
        }

        if ($animal->getUlnCountryCode() === null || $animal->getUlnNumber() === null) {
            $this->logError('Pedigree country code and number generation failed due to missing ULN data in animal', $animal);
            return $animal;
        }

        $pedigreeRegisterRegistration = $this->getPedigreeRegisterRegistration($animal);
        if (!$pedigreeRegisterRegistration) {
            return $animal;
        }

        if ($pedigreeRegisterRegistration->getPedigreeRegister() && $pedigreeRegisterRegistration->getPedigreeRegister()->getId()) {
            if (!$animal->getPedigreeRegister() ||
                ($animal->getPedigreeRegister()->getId() !== $pedigreeRegisterRegistration->getId() && $this->overwriteExistingData)) {
                $animal->setPedigreeRegister($pedigreeRegisterRegistration->getPedigreeRegister());
                $this->valueWasUpdated();
            }
        }

        $breederNumber = $this->getBreederNumber($pedigreeRegisterRegistration);
        if (!$breederNumber) {
            return $animal;
        }

        $animal = $this->fixIncongruentAnimalOrderNumber($animal);

        $newPedigreeNumber = self::generateDuplicateCheckedPedigreeNumber(
            $this->em,
            $breederNumber,
            $animal->getAnimalOrderNumber(),
            $animal->getId()
        );
        if (!$newPedigreeNumber) {
            return null;
        }

        $animal->setPedigreeNumber($newPedigreeNumber);
        $animal->setPedigreeCountryCode($animal->getUlnCountryCode());

        $this->valueWasUpdated();

        return $animal;
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function fixIncongruentAnimalOrderNumber(Animal $animal)
    {
        $extractedOrderNumber = StringUtil::getLast5CharactersFromString($animal->getUlnNumber());
        if ($extractedOrderNumber !== $animal->getAnimalOrderNumber()) {
            $animal->setAnimalOrderNumber($extractedOrderNumber);
            $this->valueWasUpdated();
        }
        return $animal;
    }


    /**
     * @param PedigreeRegisterRegistration $foundRegistration
     * @return null|string
     */
    private function getBreederNumber(PedigreeRegisterRegistration $foundRegistration)
    {
        if (!$foundRegistration) {
            return null;
        }

        if (!(is_string($foundRegistration->getBreederNumber()) && strlen($foundRegistration->getBreederNumber()) === 5)) {
            $this->logError('INVALID BREEDER NUMBER: '.$foundRegistration->getBreederNumber());
            return null;
        }

        return $foundRegistration->getBreederNumber();
    }


    /**
     * @param Animal $animal
     * @return PedigreeRegisterRegistration|null
     */
    private function getPedigreeRegisterRegistration(Animal $animal)
    {
        $locationOfBirth = $animal->getLocationOfBirth();
        if (!$locationOfBirth) {
            return null;
        }

        $registrations = $locationOfBirth->getPedigreeRegisterRegistrations();
        if (count($registrations) === 0) {
            return null;
        }


        if (!$animal->getParentMother()) {
            return null;
        }

        $biggestBreedCodeOfMother = $animal->getParentMother()->getBiggestBreedCodePartFromValidatedBreedCodeString();
        if (!$biggestBreedCodeOfMother) {
            return null;
        }


        /** @var PedigreeRegisterRegistration $registration */
        $foundRegistration = null;
        foreach ($registrations as $registration) {
            if ($registration->getPedigreeRegister()->hasPedigreeCode($biggestBreedCodeOfMother)) {
                $foundRegistration = $registration;
                break;
            }
        }

        if (!($foundRegistration instanceof PedigreeRegisterRegistration)) {
            return null;
        }

        return $foundRegistration;
    }


    /**
     * @param EntityManagerInterface $em
     * @param string $breederNumber
     * @param string $animalOrderNumber
     * @param int $animalId
     * @return string
     */
    public static function generateDuplicateCheckedPedigreeNumber(EntityManagerInterface $em, $breederNumber,
                                                                  $animalOrderNumber, $animalId)
    {
        $isFirstLoop = true;
        $newPedigreeNumber = $breederNumber . '-' . $animalOrderNumber;

        do{

            if (!$isFirstLoop) {
                $newPedigreeNumber = StringUtil::bumpPedigreeNumber($newPedigreeNumber);
            }

            $pedigreeNumberAlreadyExists = self::pedigreeNumberAlreadyExists($em, $newPedigreeNumber, $animalId);
            $isFirstLoop = false;

        } while ($pedigreeNumberAlreadyExists);

        return $newPedigreeNumber;
    }


    /**
     * @param EntityManagerInterface $em
     * @param string $pedigreeNumber
     * @param int $animalId pedigreeNumber is not considered a duplicate if it already exists for this animal
     * @return bool
     */
    public static function pedigreeNumberAlreadyExists(EntityManagerInterface $em, $pedigreeNumber, $animalId = null)
    {
        if (!$pedigreeNumber) {
            return false;
        }

        $animalIdFilter = is_int($animalId) || ctype_digit($animalId) ? ' AND id <> '.$animalId.' ' : ' ';

        $sql = "SELECT COUNT(*) as count FROM animal WHERE pedigree_number = '".$pedigreeNumber."'".$animalIdFilter;
        return $em->getConnection()->query($sql)->fetch()['count'] > 0;
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function generateMissingBreedCodes(Animal $animal)
    {
        if ($animal->getBreedCode() !== null && !$this->overwriteExistingData) {
            return $animal;
        }

        $calculatedBreedCodeChild = BreedCodeUtil::calculateBreedCodeFromParents(
            $animal->getParentFather(),
            $animal->getParentMother(),
            null,
            true
        );

        if ($calculatedBreedCodeChild !== $animal->getBreedCode()) {
            $animal->setBreedCode($calculatedBreedCodeChild);
            $this->valueWasUpdated();
        }

        return $animal;
    }


    /**
     * @param Animal $animal
     * @return Animal
     * @throws InvalidSwitchCaseException
     * @throws Error
     */
    private function generateScrapieGenotype(Animal $animal)
    {
        if ($animal->getScrapieGenotype() && !$animal->getScrapieGenotypeSource()) {
            $animal->setScrapieGenotypeSource($this->getScrapieGenotypeAdministrativeSource());
            $this->valueWasUpdated();
            return $animal;
        }

        if ($animal->getScrapieGenotype() !== null && !$this->overwriteExistingData) {
            return $animal;
        }

        /** @var Location $location */
        $location = $this->getLocation($animal);

        if ($location &&
            $location->getLocationHealth() &&
            $location->getLocationHealth()->getCurrentScrapieStatus() === ScrapieStatus::RESISTANT)
        {
            $father = $animal->getParentFather();
            $mother = $animal->getParentMother();

            if (
                $father->getScrapieGenotype() === ScrapieGenotypeType::ARR_ARR &&
                $mother->getScrapieGenotype() === ScrapieGenotypeType::ARR_ARR
            ) {
                $animal->setScrapieGenotype(ScrapieGenotypeType::ARR_ARR);
                $animal->setScrapieGenotypeSource($this->getScrapieGenotypeAdministrativeSource());
                $this->valueWasUpdated();
            } else {
                $animal->setScrapieGenotype(null);
                $animal->setScrapieGenotypeSource(null);

                $locationHealth = $location->getLocationHealth();

                $locationHealth = $this->locationHealthUpdater->setScrapieStatusToUnderObservationWhenParentsAreNonArrArrAndSendEmail($locationHealth, $animal);
                $this->locationHealthUpdater->persistNewDefaultScrapieAndHideFollowingOnes($locationHealth, new DateTime(), false);
            }
        }

        return $animal;
    }


    /**
     * @return ScrapieGenotypeSource
     */
    private function getScrapieGenotypeAdministrativeSource()
    {
        return $this->em->getRepository(ScrapieGenotypeSource::class)->getAdministrativeSource(false);
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function generateBreedType(Animal $animal)
    {
        if ($animal->getBreedType() !== null && !$this->overwriteExistingData) {
            return $animal;
        }

        $biggestBreedCodePart = $animal->getBiggestBreedCodePartFromValidatedBreedCodeString();
        switch ($biggestBreedCodePart) {
            case BreedCodeType::NH: $animal = $this->generateNHBreedType($animal); break;
            case BreedCodeType::CF: $animal = $this->generateCFBreedType($animal); break;
            case BreedCodeType::BM: $animal = $this->generateBMBreedType($animal); break;
            case BreedCodeType::TE: $animal = $this->generateTEBreedType($animal); break;
            default: break;
        }

        return $animal;
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function generateNHBreedType(Animal $animal)
    {
        // Default for Ram and Ewe
        $calculatedBreedType = BreedType::REGISTER;

        if ($animal->getGender() === GenderType::FEMALE) {
            if ($animal->getParentFather() && $animal->getParentFather()->getBreedType() === BreedType::PURE_BRED) {
                if ($animal->getParentFather()->getBreedCode() === 'NH100'
                || $animal->getParentFather()->getBreedCode() === 'NH88TE12'
                || $animal->getParentFather()->getBreedCode() === 'NH75TE25'
                || $animal->getParentFather()->getBreedCode() === 'NH50TE50'
                ) {
                    $calculatedBreedType = BreedType::PURE_BRED;
                }
            }
        }

        if ($animal->getBreedType() !== $calculatedBreedType) {
            $animal->setBreedType($calculatedBreedType);
            $this->valueWasUpdated();
        }

        return $animal;
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function generateCFBreedType(Animal $animal)
    {
        if ($animal->getBreedType() !== BreedType::REGISTER) {
            $animal->setBreedType(BreedType::REGISTER);
            $this->valueWasUpdated();
        }

        return $animal;
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function generateBMBreedType(Animal $animal)
    {
        $calculatedBreedType = BreedType::REGISTER;

        if ($animal->getDateOfBirth()
            && $this->hasPureBredValidatedBMParent($animal, true)
            && $this->hasPureBredValidatedBMParent($animal, false)
        ) {
            $calculatedBreedType = BreedType::PURE_BRED;
        }

        if ($animal->getBreedType() !== $calculatedBreedType) {
            $animal->setBreedType($calculatedBreedType);
            $this->valueWasUpdated();
        }

        return $animal;
    }


    /**
     * @param Animal $animal
     * @param $isFather
     * @return bool
     */
    private function hasPureBredValidatedBMParent(Animal $animal, $isFather)
    {
        $parent = $isFather ? $animal->getParentFather() : $animal->getParentMother();

        if (!$parent || !$parent->getDateOfBirth() || $parent->getBreedCode() !== 'BM100') {
            return false;
        }

        $age = abs(TimeUtil::getAgeInDays($parent->getDateOfBirth(), $animal->getDateOfBirth()));
        return $parent->getExteriorMeasurements()
                ->matching(ExteriorCriteria::pureBredBMParentExterior($age))
                ->count() > 0;
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function generateTEBreedType(Animal $animal)
    {
        $calculatedBreedType = BreedType::REGISTER;

        if ($this->isPureBredAndTE100($animal->getParentFather())
            && $this->hasPureBredValidatedTEParent($animal, true)
            && $this->hasPureBredValidatedTEParent($animal, false)
        ) {
            $calculatedBreedType = BreedType::PURE_BRED;
        }

        if ($animal->getBreedType() !== $calculatedBreedType) {
            $animal->setBreedType($calculatedBreedType);
            $this->valueWasUpdated();
        }

        return $animal;
    }


    /**
     * @param Animal $animal
     * @param $isFather
     * @return bool
     */
    private function hasPureBredValidatedTEParent(Animal $animal, $isFather)
    {
        $parent = $isFather ? $animal->getParentFather() : $animal->getParentMother();

        if (!$parent || !$parent->getDateOfBirth() || !$parent->getBreedCode() ||
            !$animal->getDateOfBirth() || $animal instanceof Neuter) {
            return false;
        }

        $age = abs(TimeUtil::getAgeInDays($parent->getDateOfBirth(), $animal->getDateOfBirth()));

        if ($isFather) {
            return $this->isPureBredAndTE100($parent)
                && $parent->getExteriorMeasurements()
                    ->matching(ExteriorCriteria::pureBredTEFatherExterior($age))
                    ->count() > 0;
        }

        // for Mothers
        if ($animal instanceof Ram) {

            return $this->isPureBredAndTE100($parent)
                && $parent->getExteriorMeasurements()
                    ->matching(ExteriorCriteria::pureBredTEMotherOfRamExterior($age))
                    ->count() > 0;

        }
        // animal is Ewe

        return (
                    $this->isPureBredAndTE100($parent) ||
                    BreedCodeUtil::hasBreedCodePart($parent->getBreedCode(), 'TE', 88)
               )
                && $parent->getExteriorMeasurements()
                ->matching(ExteriorCriteria::pureBredTEMotherOfEweExterior())
                ->count() > 0;
    }


    /**
     * @param Animal $animal
     * @return bool
     */
    private function isPureBredAndTE100($animal)
    {
        return $animal
            && $animal->getBreedCode() === 'TE100'
            && $animal->getBreedType() === BreedType::PURE_BRED
            ;
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function matchMissingPedigreeRegisterByBreederNumberInStn(Animal $animal)
    {
        if (!$animal || !$animal->getPedigreeNumber() ||
            ($animal->getPedigreeRegister() && $animal->getPedigreeRegister()->getId())
        ) {
            return $animal;
        }

        $breederNumber = StringUtil::getBreederNumberFromPedigreeNumber($animal->getPedigreeNumber());
        $pedigreeRegister = $this->em->getRepository(PedigreeRegister::class)->findOneByBreederNumber($breederNumber);

        if (!$pedigreeRegister) {
            return $animal;
        }

        if (!$animal->getPedigreeRegister() ||
            $animal->getPedigreeRegister()->getId() !== $pedigreeRegister->getId()) {
            $animal->setPedigreeRegister($pedigreeRegister);
            $this->valueWasUpdated();
        }

        return $animal;
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function batchMatchMissingPedigreeRegisterByBreederNumberInStn()
    {
        $updateCount = 0;
        foreach (['TRUE', 'FALSE'] as $boolVal) {
            $sql = "UPDATE animal SET pedigree_register_id = v.pedigree_register_id
                FROM (
                  SELECT
                    a.id as animal_id,
                    pedigree_number,
                    prr.pedigree_register_id
                  FROM animal a
                    INNER JOIN (
                                 SELECT
                                   breeder_number,
                                   MAX(pedigree_register_id) as pedigree_register_id
                                 FROM pedigree_register_registration
                                 WHERE is_active = $boolVal
                                 GROUP BY breeder_number
                               )prr ON prr.breeder_number = substr(pedigree_number, 1, 5)
                  WHERE a.pedigree_register_id ISNULL
                ) AS v(animal_id, pedigree_number, pedigree_register_id) WHERE animal.id = v.animal_id 
                AND animal.pedigree_register_id ISNULL";

            $updateCount += SqlUtil::updateWithCount($this->em->getConnection(), $sql);
        }

        $this->logger->notice(($updateCount === 0 ? 'No' : $updateCount).' missing pedigreeRegisterIds were matched to animals');

        return $updateCount;
    }


    /**
     * @param string $message
     * @param Animal $animal
     */
    private function logError($message, $animal = null)
    {
        $animalData = $animal ? ' [animalId: '.$animal->getId().', uln: '.$animal->getUln().']' : '';
        $this->logger->error($message . $animalData);
    }


    private function resetCounters()
    {
        $this->inBatchSize = 0;
        $this->totalUpdateCount = 0;
        $this->lastFlushedAnimalId = 0;
        $this->lastCheckedAnimalId = 0;
        $this->startAnimalId = 0;
    }
}