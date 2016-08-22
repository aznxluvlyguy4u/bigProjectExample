<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\GenderType;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Enumerator\AnimalType;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Ewe
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EweRepository")
 * @package AppBundle\Entity
 */
class Ewe extends Animal
{
    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentMother")
     * @JMS\Type("AppBundle\Entity\Ewe")
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="surrogate")
     * @JMS\Type("AppBundle\Entity\Ewe")
     */
    protected $surrogateChildren;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
     protected $objectType;

      /**
       * @var ArrayCollection
       * 
       * @ORM\OneToMany(targetEntity="Litter", mappedBy="animalMother")
       * @JMS\Type("AppBundle\Entity\Ewe")
       */
    private $litters;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("AppBundle\Entity\Mate")
     * @ORM\OneToMany(targetEntity="Mate", mappedBy="studEwe")
     */
    private $matings;

    /**
     * Ewe constructor.
     */
     public function __construct() {
         //Call super constructor first
         parent::__construct();

         $this->objectType = "Ewe";
         $this->setAnimalType(AnimalType::sheep);
         $this->setGender(GenderType::FEMALE);
         $this->setAnimalCategory(3);
       
         $this->litters = new ArrayCollection();
         $this->children = new ArrayCollection();
     }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Ewe
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * Get objectType
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\Animal $child
     *
     * @return Ewe
     */
    public function addChild(\AppBundle\Entity\Animal $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\Animal $child
     */
    public function removeChild(\AppBundle\Entity\Animal $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    
    /**
     * @return ArrayCollection
     */
    public function getMatings()
    {
        return $this->matings;
    }

    /**
     * @param ArrayCollection $matings
     */
    public function setMatings($matings)
    {
        $this->matings = $matings;
    }
    

    /**
     * Set isAlive
     *
     * @param boolean $isAlive
     *
     * @return Ewe
     */
    public function setIsAlive($isAlive)
    {
        $this->isAlive = $isAlive;

        return $this;
    }

    /**
     * Get isAlive
     *
     * @return boolean
     */
    public function getIsAlive()
    {
        return $this->isAlive;
    }

    /**
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return Ewe
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return Ewe
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Set animalOrderNumber
     *
     * @param string $animalOrderNumber
     *
     * @return Ewe
     */
    public function setAnimalOrderNumber($animalOrderNumber)
    {
        $this->animalOrderNumber = $animalOrderNumber;

        return $this;
    }

    /**
     * Add export
     *
     * @param \AppBundle\Entity\DeclareExport $export
     *
     * @return Ewe
     */
    public function addExport(\AppBundle\Entity\DeclareExport $export)
    {
        $this->exports[] = $export;

        return $this;
    }

    /*
     * Remove departure
     *
     * @param \AppBundle\Entity\DeclareDepart $departure
     */
    public function removeDeparture(\AppBundle\Entity\DeclareDepart $departure)
    {
        $this->departures->removeElement($departure);
    }

    /**
     * Add surrogateChild
     *
     * @param \AppBundle\Entity\Animal $surrogateChild
     *
     * @return Ewe
     */
    public function addSurrogateChild(\AppBundle\Entity\Animal $surrogateChild)
    {
        $this->surrogateChildren[] = $surrogateChild;

        return $this;
    }

    /**
     * Remove export
     *
     * @param \AppBundle\Entity\DeclareExport $export
     */
    public function removeExport(\AppBundle\Entity\DeclareExport $export)
    {
        $this->exports->removeElement($export);
    }

    /**
     * Get exports
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExports()
    {
        return $this->exports;
    }

    /*
     * Remove surrogateChild
     *
     * @param \AppBundle\Entity\Animal $surrogateChild
     */
    public function removeSurrogateChild(\AppBundle\Entity\Animal $surrogateChild)
    {
        $this->surrogateChildren->removeElement($surrogateChild);
    }

    /**
     * Get surrogateChildren
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSurrogateChildren()
    {
        return $this->surrogateChildren;
    }

    /**
     * Set surrogate
     *
     * @param \AppBundle\Entity\Ewe $surrogate
     *
     * @return Ewe
     */
    public function setSurrogate(\AppBundle\Entity\Ewe $surrogate = null)
    {
        $this->surrogate = $surrogate;

        return $this;
    }

    /**
     * Get surrogate
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getSurrogate()
    {
        return $this->surrogate;
    }

    /**
     * Add flag
     *
     * @param \AppBundle\Entity\DeclareAnimalFlag $flag
     *
     * @return Ewe
     */
    public function addFlag(\AppBundle\Entity\DeclareAnimalFlag $flag)
    {
        $this->flags[] = $flag;

        return $this;
    }

    /**
     * Remove flag
     *
     * @param \AppBundle\Entity\DeclareAnimalFlag $flag
     */
    public function removeFlag(\AppBundle\Entity\DeclareAnimalFlag $flag)
    {
        $this->flags->removeElement($flag);
    }

    /**
     * Get flags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Get weightMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWeightMeasurements()
    {
        return $this->weightMeasurements;
    }

    /**
     * Add animalResidenceHistory
     *
     * @param \AppBundle\Entity\AnimalResidence $animalResidenceHistory
     *
     * @return Ewe
     */
    public function addAnimalResidenceHistory(\AppBundle\Entity\AnimalResidence $animalResidenceHistory)
    {
        $this->animalResidenceHistory[] = $animalResidenceHistory;

        return $this;
    }

    /**
     * Remove animalResidenceHistory
     *
     * @param \AppBundle\Entity\AnimalResidence $animalResidenceHistory
     */
    public function removeAnimalResidenceHistory(\AppBundle\Entity\AnimalResidence $animalResidenceHistory)
    {
        $this->animalResidenceHistory->removeElement($animalResidenceHistory);
    }

    /**
     * Set breedType
     *
     * @param string $breedType
     *
     * @return Ewe
     */
    public function setBreedType($breedType)
    {
        $this->breedType = $breedType;

        return $this;
    }

    /**
     * Get breedType
     *
     * @return string
     */
    public function getBreedType()
    {
        return $this->breedType;
    }

    /**
     * Set breedCode
     *
     * @param string $breedCode
     *
     * @return Ewe
     */
    public function setBreedCode($breedCode)
    {
        $this->breedCode = $breedCode;

        return $this;
    }

    /**
     * Get breedCode
     *
     * @return string
     */
    public function getBreedCode()
    {
        return $this->breedCode;
    }

    /**
     * Add litter
     *
     * @param \AppBundle\Entity\Litter $litter
     *
     * @return Ewe
     */
    public function addLitter(\AppBundle\Entity\Litter $litter)
    {
        $this->litters[] = $litter;

        return $this;
    }

    /**
     * Remove litter
     *
     * @param \AppBundle\Entity\Litter $litter
     */
    public function removeLitter(\AppBundle\Entity\Litter $litter)
    {
        $this->litters->removeElement($litter);
    }

    /**
     * Get litters
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLitters()
    {
        return $this->litters;
    }

    /**
     * Set scrapieGenotype
     *
     * @param string $scrapieGenotype
     *
     * @return Ewe
     */
    public function setScrapieGenotype($scrapieGenotype)
    {
        $this->scrapieGenotype = $scrapieGenotype;

        return $this;
    }

    /**
     * Get scrapieGenotype
     *
     * @return string
     */
    public function getScrapieGenotype()
    {
        return $this->scrapieGenotype;
    }

    /**
     * Add exteriorMeasurement
     *
     * @param \AppBundle\Entity\Exterior $exteriorMeasurement
     *
     * @return Ewe
     */
    public function addExteriorMeasurement(\AppBundle\Entity\Exterior $exteriorMeasurement)
    {
        $this->exteriorMeasurements[] = $exteriorMeasurement;

        return $this;
    }

    /**
     * Remove exteriorMeasurement
     *
     * @param \AppBundle\Entity\Exterior $exteriorMeasurement
     */
    public function removeExteriorMeasurement(\AppBundle\Entity\Exterior $exteriorMeasurement)
    {
        $this->exteriorMeasurements->removeElement($exteriorMeasurement);
    }

    /**
     * Get exteriorMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExteriorMeasurements()
    {
        return $this->exteriorMeasurements;
    }

    /**
     * Add parent
     *
     * @param \AppBundle\Entity\Animal $parent
     *
     * @return Ewe
     */
    public function addParent(\AppBundle\Entity\Animal $parent)
    {
        $this->parents[] = $parent;

        return $this;
    }

    /**
     * Remove parent
     *
     * @param \AppBundle\Entity\Animal $parent
     */
    public function removeParent(\AppBundle\Entity\Animal $parent)
    {
        $this->parents->removeElement($parent);
    }

    /**
     * Get parents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * Set parentNeuter
     *
     * @param \AppBundle\Entity\Neuter $parentNeuter
     *
     * @return Ewe
     */
    public function setParentNeuter(\AppBundle\Entity\Neuter $parentNeuter = null)
    {
        $this->parentNeuter = $parentNeuter;

        return $this;
    }

    /**
     * Get parentNeuter
     *
     * @return \AppBundle\Entity\Neuter
     */
    public function getParentNeuter()
    {
        return $this->parentNeuter;
    }

    
}
