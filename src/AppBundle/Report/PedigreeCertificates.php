<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class PedigreeCertificates
 */
class PedigreeCertificates
{
    /** @var array */
    private $reports;

    /** @var Client */
    private $client;

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
        $this->reports = array();
        $this->client = $client;

        $animals = self::getAnimalsInContentArray($em, $content);
        $this->animalCount = 0;

        foreach ($animals as $animal) {
            $pedigreeCertificate = new PedigreeCertificate($em, $client, $location, $animal, $generationOfAscendants);

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

    /**
     * @param string $mainDirectory
     * @return string
     */
    public function getFilePath($mainDirectory)
    {
        return $mainDirectory.'/'.$this->getS3Key();
    }

    public function getFileName()
    {
        $dateTimeNow = new \DateTime();
        $datePrint = $dateTimeNow->format('Y-m-d_').$dateTimeNow->format('H').'h'.$dateTimeNow->format('i').'m'.$dateTimeNow->format('s').'s';

        return 'afstammingsbewijs-'.$datePrint.'.pdf';
    }

    public function getS3Key()
    {
        //TODO when each client has a permanent unique identifier, replace the id with that identifier.
        return 'reports/'.$this->client->getId().'/'.$this->getFileName();
    }
}