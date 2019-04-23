<?php

namespace AppBundle\Report;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Output\BreedValuesOutput;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

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

    /** @var PedigreeCertificate */
    private $generator;

    /** @var BreedValuesOutput */
    private $breedValuesOutput;

    public function __construct(EntityManagerInterface $em, PedigreeCertificate $generator)
    {
        parent::__construct($em, null, self::FILE_NAME_REPORT_TYPE);
        $this->generator = $generator;
    }

    /**
     * @required
     *
     * @param BreedValuesOutput $breedValuesOutput
     */
    public function setBreedValuesOutput(BreedValuesOutput $breedValuesOutput)
    {
        $this->breedValuesOutput = $breedValuesOutput;
    }

    /**
     * @required
     */
    public function initializeConstants()
    {
        $this->fileNameType = self::FILE_NAME_REPORT_TYPE;
    }

    /**
     * Create the data for the PedigreeCertificate.
     * Before this is run, it is assumed all the ulns have been verified.
     *
     * @param Person $actionBy
     * @param Collection $content containing the ulns of multiple animals
     * @param Client $client
     * @param Location $location
     * @throws \Exception
     */
    public function generate(Person $actionBy, Collection $content, $client, $location)
    {
        $this->reports = array();
        $this->client = $client;

        $animalIds = self::getAnimalsInContentArray($this->em, $content);
        $this->animalCount = 0;

        if($client == null && $location == null) { //user is admin
            $companyName = null;
            $trimmedCompanyName = null;
            $ownerEmailAddress = null;
            $companyAddress = null;
            $ubn = null;
        } else {
            $companyName = $this->getCompanyName($location, $client);
            $trimmedCompanyName = StringUtil::trimStringWithAddedEllipsis($companyName, self::MAX_LENGTH_FULL_NAME);
            $ownerEmailAddress = $client->getEmailAddress();
            $companyAddress = $location->getCompany()->getAddress();
            $ubn = $location->getUbn();
        }

        foreach ($animalIds as $animalId) {
            $this->reports[$this->animalCount] = $this->generator
                ->generate($actionBy, $ubn, $animalId, $trimmedCompanyName, $ownerEmailAddress, $companyAddress);

            $this->animalCount++;
        }
        $this->breedValuesOutput->clearPrivateValues();
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
