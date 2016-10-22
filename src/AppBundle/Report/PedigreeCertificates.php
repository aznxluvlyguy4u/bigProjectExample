<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
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
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class PedigreeCertificates
 */
class PedigreeCertificates extends ReportBase
{
    const FILE_NAME_REPORT_TYPE = 'afstammingsbewijs';
    const MAX_LENGTH_FULL_NAME = 30;

    /** @var array */
    private $reports;

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
     */
    public function __construct(ObjectManager $em, Collection $content, Client $client,
                                Location $location)
    {
        parent::__construct($em, $client, self::FILE_NAME_REPORT_TYPE);
        
        $this->reports = array();
        $this->client = $client;

        $animalIds = self::getAnimalsInContentArray($em, $content);
        $this->animalCount = 0;

        /** @var GeneticBaseRepository $geneticBaseRepository */
        $geneticBaseRepository = $em->getRepository(GeneticBase::class);

        $breedValuesYear = $geneticBaseRepository->getLatestYear();
        $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

        /** @var BreedValueCoefficientRepository $breedValueCoefficientRepository */
        $breedValueCoefficientRepository = $em->getRepository(BreedValueCoefficient::class);
        $lambMeatIndexCoefficients = $breedValueCoefficientRepository->getLambMeatIndexCoefficients();

        $companyName = $this->getCompanyName($location, $client);
        $trimmedClientName = StringUtil::trimStringWithAddedEllipsis($companyName, self::MAX_LENGTH_FULL_NAME);
        $companyAddress = $location->getCompany()->getAddress();
        $ubn = $location->getUbn();

        foreach ($animalIds as $animalId) {
            $pedigreeCertificate = new PedigreeCertificate($em, $client, $ubn, $animalId, $breedValuesYear, $geneticBases, $lambMeatIndexCoefficients, $trimmedClientName, $companyAddress);

            $this->reports[$this->animalCount] = $pedigreeCertificate->getData();

            $this->animalCount++;
        }
    }

    /**
     * @param ObjectManager $em
     * @param Collection $content
     * @return array
     */
    private function getAnimalsInContentArray(ObjectManager $em, Collection $content)
    {
        $animalIds = [];
        
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);

        foreach ($content->getKeys() as $key) {
            if ($key == Constant::ANIMALS_NAMESPACE) {
                $animalArrays = $content->get($key);

                foreach ($animalArrays as $animalArray) {
                    $ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
                    $ulnCountryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
                    $animalId = $animalRepository->sqlQueryAnimalIdByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);

                    $animalIds[] = $animalId;
                }
            }
        }
        
        return $animalIds;
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


    /**
     * @param Location $location
     * @param Client $client
     * @return string
     */
    private function getCompanyName($location, $client)
    {
        $company = $location->getCompany();
        if ($company != null) {
            return $company->getCompanyName();
        } else {
            $company = $client->getCompanies()->first();
            if ($company != null) {
                return $company->getCompanyName();
            } else {
                return '-';
            }
        }
    }
    
}