<?php

namespace AppBundle\JsonFormat;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Tag;

/**
 * Class JsonFormatAnimalBase
 * @package AppBundle\JsonFormat
 */
class JsonFormatAnimalBase
{
    /**
     * @var string
     */
    private $pedigreeCountryCode;

    /**
     * @var string
     */
    private $pedigreeNumber;

    /**
     * @var string
     */
    private $ulnCountryCode;

    /**
     * @var string
     */
    private $ulnNumber;

    /**
     * @param Animal $animal
     */
    protected function setAnimalUln(Animal $animal)
    {
        $this->setUlnCountryCode($animal->getUlnCountryCode());
        $this->setUlnNumber($animal->getUlnNumber());
    }

    /**
     * @param Animal $animal
     */
    protected function setAnimalPedigree(Animal $animal)
    {
        $this->setPedigreeCountryCode($animal->getPedigreeCountryCode());
        $this->setPedigreeNumber($animal->getPedigreeNumber());
    }

    /**
     * @param Animal $animal
     */
    protected function setAnimalUlnAndPedigree(Animal $animal)
    {
        $this->setAnimalUln($animal);
        $this->setAnimalPedigree($animal);
    }

    /**
     * @return string
     */
    public function getPedigreeCountryCode()
    {
        return $this->pedigreeCountryCode;
    }

    /**
     * @param string $pedigreeCountryCode
     */
    public function setPedigreeCountryCode($pedigreeCountryCode)
    {
        $this->pedigreeCountryCode = $pedigreeCountryCode;
    }

    /**
     * @return string
     */
    public function getPedigreeNumber()
    {
        return $this->pedigreeNumber;
    }

    /**
     * @param string $pedigreeNumber
     */
    public function setPedigreeNumber($pedigreeNumber)
    {
        $this->pedigreeNumber = $pedigreeNumber;
    }

    /**
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * @param string $ulnCountryCode
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;
    }

    /**
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * @param string $ulnNumber
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;
    }

    /**
     * @param Tag $tag
     */
    public function setAssignedTag(Tag $tag)
    {
        $this->setUlnCountryCode($tag->getUlnCountryCode());
        $this->setUlnNumber($tag->getUlnNumber());
    }
}