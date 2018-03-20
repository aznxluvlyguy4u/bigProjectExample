<?php


namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Util\BreedCodeUtil;
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
     * @param Animal[] $animals
     * @param Location $location
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

        foreach ($animals as $key => $animal) {
            $animals[$key] = $this->generatePedigreeData($animal);

            if ($this->inBatchSize%$batchSize === 0) {
                $this->em->flush();
            }
        }

        if ($this->isAnyValueUpdated) {
            $this->em->flush();
        }

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
        $animal = $this->generatePedigreeData($animal);
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


        // TODO

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
        if ($animal->getScrapieGenotype() !== null && !$this->overwriteExistingData) {
            return $animal;
        }

        // TODO

        $this->valueWasUpdated();

        return $animal;
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
    private function logError($message, Animal $animal)
    {
        $animalData = $animal ? ' [animalId: '.$animal->getId().', uln: '.$animal->getUln().']' : '';
        $this->logger->error($message . $animalData);
    }


}