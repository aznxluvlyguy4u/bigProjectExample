<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BreedValueCoefficient;
use AppBundle\Entity\BreedValueCoefficientRepository;
use AppBundle\Entity\BreedValuesSet;
use AppBundle\Entity\BreedValuesSetRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\GeneticBase;
use AppBundle\Entity\GeneticBaseRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Enumerator\BreedValueCoefficientType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class PedigreeCertificates
 */
class PedigreeCertificates extends ReportBase
{
    const FILE_NAME_REPORT_TYPE = 'afstammingsbewijs';

    /** @var array */
    private $reports;

    /** @var string */
    private $ulnOfLastChild;

    /** @var int */
    private $animalCount;

    /**
     * Create the data for the PedigreeCertificate.
     * Before this is run, it is assumed all the ulns have been verified.
     *
     * @param ObjectManager $em
     * @param Collection $content containing the ulns of multiple animals
     * @param Client $client
     * @param Location $location
     * @param int $generationOfAscendants
     */
    public function __construct(ObjectManager $em, Collection $content, Client $client,
                                Location $location, $generationOfAscendants = 3)
    {
        parent::__construct($em, $client, self::FILE_NAME_REPORT_TYPE);
        
        $this->reports = array();
        $this->client = $client;

        $animals = self::getAnimalsInContentArray($em, $content);
        $this->animalCount = 0;

        /** @var GeneticBaseRepository $geneticBaseRepository */
        $geneticBaseRepository = $em->getRepository(GeneticBase::class);

        $breedValuesYear = $geneticBaseRepository->getLatestYear();
        $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

        /** @var BreedValueCoefficientRepository $breedValueCoefficientRepository */
        $breedValueCoefficientRepository = $em->getRepository(BreedValueCoefficient::class);
        $lambMeatIndexCoefficients = $breedValueCoefficientRepository->getLambMeatIndexCoefficients();

        foreach ($animals as $animal) {
            $pedigreeCertificate = new PedigreeCertificate($em, $client, $location, $animal, $generationOfAscendants, $breedValuesYear, $geneticBases, $lambMeatIndexCoefficients);

            $this->reports[$this->animalCount] = $pedigreeCertificate->getData();

            $this->animalCount++;
            $this->ulnOfLastChild = $animal->getUlnCountryCode() . $animal->getUlnNumber();
        }
    }

    /**
     * @param ObjectManager $em
     * @param Collection $content
     * @return ArrayCollection
     */
    private function getAnimalsInContentArray(ObjectManager $em, Collection $content)
    {
        $animals = new ArrayCollection();

        foreach ($content->getKeys() as $key) {
            if ($key == Constant::ANIMALS_NAMESPACE) {
                $animalArrays = $content->get($key);

                foreach ($animalArrays as $animalArray) {
                    $ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
                    $ulnCountryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
                    $animal = $em->getRepository(Animal::class)->findByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);

                    $animals->add($animal);
                }
            }
        }

        
        return $animals;
    }

    /**
     * Array of arrays containing properly labelled variables for twig file.
     *
     * @return array
     */
    public function getReports()
    {
        return $this->reports;
    }

    /**
     * @return int
     */
    public function getAnimalCount()
    {
        return $this->animalCount;
    }

}