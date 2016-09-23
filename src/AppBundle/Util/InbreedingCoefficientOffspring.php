<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Ram;
use Doctrine\Common\Persistence\ObjectManager;

class InbreedingCoefficientOffspring
{

    /** @var ObjectManager */
    private $em;

    /** @var AnimalRepository */
    private $animalRepository;

    /** @var array */
    private $parentSearchArray;

    /** @var array */
    private $childrenSearchArray;

    /** @var float */
    private $inbreedingCoefficient;

    /** @var Ram */
    private $father;

    /** @var Ewe */
    private $mother;

    /**
     * InbreedingCoefficientOffspring constructor.
     * @param ObjectManager $em
     * @param Ram $father
     * @param Ewe $mother
     */
    public function __construct(ObjectManager $em, Ram $father, Ewe $mother)
    {
        $this->em = $em;
        $this->animalRepository = $em->getRepository(Animal::class);
        $this->father = $father;
        $this->mother = $mother;

        $this->parentSearchArray = array();
        $this->childrenSearchArray = array();

        $this->inbreedingCoefficient = $this->calculateInbreedingCoefficient($father, $mother);
    }


    /**
     * @return float
     */
    public function getValue()
    {
        return $this->inbreedingCoefficient;
    }

    /**
     * @param string $separator
     * @return null|string
     */
    public function getUlnFather($separator = '')
    {
        return AnimalArrayReader::getIdString($this->father, $separator);
    }


    /**
     * @param string $separator
     * @return null|string
     */
    public function getUlnMother($separator = '')
    {
        return AnimalArrayReader::getIdString($this->mother, $separator);
    }


    /**
     * @param Ram $father
     * @param Ewe $mother
     * @return float
     */
    private function calculateInbreedingCoefficient(Ram $father, Ewe $mother)
    {
        return 0.0;
    }

}


/**
 * Class InbreedingCoefficient
 * @package AppBundle\Util
 */
class InbreedingCoefficient
{

    /** @var  */
    private $inbreedingCoefficientOffspring;
    
    /**
     * InbreedingCoefficient constructor.
     * @param ObjectManager $em
     * @param Animal $animal
     */
    public function __construct(ObjectManager $em, $animal)
    {
        $this->inbreedingCoefficientOffspring = new InbreedingCoefficientOffspring($em, $animal->getParentFather(),$animal->getParentMother());
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->inbreedingCoefficientOffspring->getValue();
    }
}