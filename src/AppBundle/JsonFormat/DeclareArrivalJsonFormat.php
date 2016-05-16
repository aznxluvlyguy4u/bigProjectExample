<?php

namespace AppBundle\JsonFormat;


use AppBundle\Entity\Animal;
use AppBundle\JsonFormat\JsonFormatAnimalBase;

/**
 * Class DeclareArrivalJsonFormat
 * @package AppBundle\JsonFormat
 */
class DeclareArrivalJsonFormat
{
    /**
     * @var boolean
     */
    private $isImportAnimal;

    /**
     * @var string
     */
    private $ubnPreviousOwner;

    /**
     * @var \DateTime
     */
    private $arrivalDate;

    /**
     * @var \AppBundle\JsonFormat\JsonFormatAnimalBase
     */
    private $animal;

    public function __construct()
    {
        $this->animal = new JsonFormatAnimalBase();
    }

    /**
     * @return boolean
     */
    public function getIsImportAnimal()
    {
        return $this->isImportAnimal;
    }

    /**
     * @param boolean $isImportAnimal
     */
    public function setIsImportAnimal($isImportAnimal)
    {
        $this->isImportAnimal = $isImportAnimal;
    }

    /**
     * @return string
     */
    public function getUbnPreviousOwner()
    {
        return $this->ubnPreviousOwner;
    }

    /**
     * @param string $ubnPreviousOwner
     */
    public function setUbnPreviousOwner($ubnPreviousOwner)
    {
        $this->ubnPreviousOwner = $ubnPreviousOwner;
    }

    /**
     * @return \DateTime
     */
    public function getArrivalDate()
    {
        return $this->arrivalDate;
    }

    /**
     * @param \DateTime $arrivalDate
     */
    public function setArrivalDate($arrivalDate)
    {
        $this->arrivalDate = $arrivalDate;
    }

    /**
     * @return JsonFormatAnimalBase
     */
    public function getAnimalJsonFormat()
    {
        return $this->animal;
    }

    /**
     * @param JsonFormatAnimalBase $animalJsonFormat
     */
    public function setAnimalJsonFormat($animalJsonFormat)
    {
        $this->animal = $animalJsonFormat;
    }

    /**
     * @param Animal $animal
     * @param bool $usePedigree
     */
    public function setAnimal($animal, $usePedigree = false)
    {
        if($usePedigree == false) {
            $this->animal->setUlnCountryCode($animal->getUlnCountryCode());
            $this->animal->setUlnNumber($animal->getUlnNumber());

        } else {
            $this->animal->setPedigreeCountryCode($animal->getPedigreeCountryCode());
            $this->animal->setPedigreeNumber($animal->getPedigreeNumber());
        }
    }


}