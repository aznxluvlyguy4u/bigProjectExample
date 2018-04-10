<?php


namespace AppBundle\Service;


use AppBundle\Criteria\ExteriorCriteria;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\PedigreeRegisterRegistration;
use AppBundle\Entity\Ram;
use AppBundle\Entity\ScrapieGenotypeSource;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\ScrapieGenotypeType;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

class PedigreeDataGenerator
{
    const BATCH_SIZE = 1000;

    /** @var EntityManagerInterface */
    private $em;
    /** @var Logger */
    private $logger;

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

    public function __construct(EntityManagerInterface $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param Animal[] $animals The animals array should contain the necessary pedigree and parent data.
     * @param Location $location The current location of all given animals. If empty the current location of each animal is used.
     * @param boolean $overwriteExistingData
     * @param int $batchSize
     * @return Animal[]
     */
    public function generate($animals, $location, $overwriteExistingData, $batchSize = self::BATCH_SIZE)
    {
        $this->isAnyValueUpdated = false;
        $this->location = $location;
        $this->overwriteExistingData = $overwriteExistingData;
        $this->inBatchSize = 0;

        try {

            foreach ($animals as $key => $animal) {
                $animals[$key] = $this->generatePedigreeData($animal);

                if ($this->inBatchSize%$batchSize === 0) {
                    $this->em->flush();
                }
            }

            if ($this->isAnyValueUpdated) {
                $this->em->flush();
            }

        } catch (\Exception $exception) {
            $this->logger->error($exception->getTraceAsString());
            $this->logger->error($exception->getMessage());
        }

        $this->em->getRepository(ScrapieGenotypeSource::class)->clearSearchArrays();

        return $animals;
    }


    /**
     * @param Animal $animal
     * @return Animal
     */
    private function generatePedigreeData(Animal $animal)
    {
        $this->isAnimalValueUpdated = false;

        // NOTE! Run these functions in this order!
        $animal = $this->generateMissingBreedCodes($animal);
        $animal = $this->generatePedigreeCountryCodeAndNumber($animal);
        $animal = $this->generateScrapieGenotype($animal);
        $animal = $this->generateBreedType($animal);

        if ($this->isAnimalValueUpdated) {
            $this->em->persist($animal);
            $this->inBatchSize++;
        }

        return $animal;
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
    private function generatePedigreeCountryCodeAndNumber(Animal $animal)
    {
        if($animal->getPedigreeCountryCode() !== null && $animal->getPedigreeNumber() !== null
        && !$this->overwriteExistingData) {
            return $animal;
        }

        if ($animal->getUlnCountryCode() === null || $animal->getUlnNumber() === null) {
            $this->logError('Pedigree country code and number generation failed due to missing ULN data in animal', $animal);
            return $animal;
        }

        $breederNumber = $this->getBreederNumber($animal);
        if (!$breederNumber) {
            return $animal;
        }

        $animal = $this->fixIncongruentAnimalOrderNumber($animal);

        $newPedigreeNumber = self::generateDuplicateCheckedPedigreeNumber($this->em, $breederNumber, $animal->getAnimalOrderNumber());
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
     * @param Animal $animal
     * @return null|string
     */
    private function getBreederNumber(Animal $animal)
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

        if (!(is_string($foundRegistration->getBreederNumber()) && strlen($foundRegistration->getBreederNumber()) === 5)) {
            $this->logError('INVALID BREEDER NUMBER: '.$foundRegistration->getBreederNumber(), $animal);
            return null;
        }

        return $registration->getBreederNumber();
    }


    /**
     * @param EntityManagerInterface $em
     * @param string $breederNumber
     * @param string $animalOrderNumber
     * @return string
     */
    public static function generateDuplicateCheckedPedigreeNumber(EntityManagerInterface $em, $breederNumber, $animalOrderNumber)
    {
        $isFirstLoop = true;
        $newPedigreeNumber = $breederNumber . '-' . $animalOrderNumber;

        do{

            if (!$isFirstLoop) {
                $newPedigreeNumber = StringUtil::bumpPedigreeNumber($newPedigreeNumber);
            }

            $pedigreeNumberAlreadyExists = self::pedigreeNumberAlreadyExists($em, $newPedigreeNumber);
            $isFirstLoop = false;

        } while ($pedigreeNumberAlreadyExists);

        return $newPedigreeNumber;
    }


    /**
     * @param EntityManagerInterface $em
     * @param string $pedigreeNumber
     * @return bool
     */
    public static function pedigreeNumberAlreadyExists(EntityManagerInterface $em, $pedigreeNumber)
    {
        if (!$pedigreeNumber) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM animal WHERE pedigree_number = '".$pedigreeNumber."'";
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

        $breedCodeChild = BreedCodeUtil::calculateBreedCodeFromParents(
            $animal->getParentFather(),
            $animal->getParentMother(),
            null,
            true
        );

        $animal->setBreedCode($breedCodeChild);

        $this->valueWasUpdated();

        return $animal;
    }


    /**
     * @param Animal $animal
     * @return Animal
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

        if ($this->getLocation($animal) &&
            $this->getLocation($animal)->getLocationHealth() &&
            $this->getLocation($animal)->getLocationHealth()->getCurrentScrapieStatus() === ScrapieStatus::RESISTANT)
        {
            $animal->setScrapieGenotype(ScrapieGenotypeType::ARR_ARR);
            $animal->setScrapieGenotype($this->getScrapieGenotypeAdministrativeSource());
            $this->valueWasUpdated();
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
     * @param string $message
     * @param Animal $animal
     */
    private function logError($message, $animal = null)
    {
        $animalData = $animal ? ' [animalId: '.$animal->getId().', uln: '.$animal->getUln().']' : '';
        $this->logger->error($message . $animalData);
    }


}