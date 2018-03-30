<?php


namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\PedigreeRegisterRegistration;
use AppBundle\Entity\ScrapieGenotypeSource;
use AppBundle\Enumerator\ScrapieGenotypeType;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\StringUtil;
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
            // TODO log errors
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

        $animal = $this->generatePedigreeCountryCodeAndNumber($animal);
        $animal = $this->generateMissingBreedCodes($animal);
        $animal = $this->generatePedigreeRegister($animal);
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


    private function getBreederNumber($animal)
    {
        $registrations = $this->getLocation($animal)->getPedigreeRegisterRegistrations();
        if (count($registrations) === 0) {
            return null;
        }

        $registration = null;
        if (count($registrations) === 1) {
            $registration = $registrations->first();
            // TODO CHECK IF PEDIGREE MATCHES
        }

        // count > 1
        // TODO FIND MATCHING PEDIGREE

        if (!($registration instanceof PedigreeRegisterRegistration)) {
            return null;
        }

        if (!(is_string($registration->getBreederNumber()) && strlen($registration->getBreederNumber()) === 5)) {
            $this->logError('INVALID BREEDER NUMBER: '.$registration->getBreederNumber(), $animal);
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


    private function generatePedigreeRegister(Animal $animal)
    {
        if ($animal->getPedigreeRegister() !== null && !$this->overwriteExistingData) {
            return $animal;
        }

        // TODO ?

        $this->valueWasUpdated();

        return $animal;
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
            $animal->setScrapieGenotype($this->getScrapieGenotypeAdministrativeSource());
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
        return $this->em->getRepository(ScrapieGenotypeSource::class)->getAdministrativeSource();
    }



    private function generateBreedType(Animal $animal)
    {
        if ($animal->getBreedType() !== null && !$this->overwriteExistingData) {
            return $animal;
        }

        // TODO

        $this->valueWasUpdated();

        return $animal;
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