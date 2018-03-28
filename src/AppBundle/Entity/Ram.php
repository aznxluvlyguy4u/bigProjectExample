<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Ram
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RamRepository")
 * @package AppBundle\Entity
 *
 */
class Ram extends Animal
{
    use EntityClassInfo;

    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentFather")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Animal>")
     */
    private $children;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     * 
     */
     private $objectType;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Litter", mappedBy="animalFather")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Litter>")
     * @ORM\OrderBy({"litterDate" = "ASC"})
     */
    private $litters;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Mate>")
     * @ORM\OneToMany(targetEntity="Mate", mappedBy="studRam")
     */
    private $matings;

    /**
     * Ram constructor.
     */
     public function __construct() {
         //Call super constructor first
         parent::__construct();

         $this->objectType = "Ram";
         $this->setAnimalType(AnimalType::sheep);
         $this->setAnimalCategory(3);
         $this->setGender(GenderType::MALE);

         $this->litters = new ArrayCollection();
         $this->children = new ArrayCollection();
    }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
     */
    public function addExport(\AppBundle\Entity\DeclareExport $export)
    {
        $this->exports[] = $export;

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
     * Remove departure
     *
     * @param \AppBundle\Entity\DeclareDepart $departure
     */
    public function removeDeparture(\AppBundle\Entity\DeclareDepart $departure)
    {
        $this->departures->removeElement($departure);
    }

    /**
     * Set surrogate
     *
     * @param \AppBundle\Entity\Ewe $surrogate
     *
     * @return Ram
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
     * @return Ram
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
     * Add animalResidenceHistory
     *
     * @param \AppBundle\Entity\AnimalResidence $animalResidenceHistory
     *
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
    public function getBreedCode() {
        return $this->breedCode;
    }

    /**
     * Set scrapieGenotype
     *
     * @param string $scrapieGenotype
     *
     * @return Ram
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
     * @return Ram
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
     * @return ArrayCollection
     */
    public function getExteriorMeasurements()
    {
        return $this->exteriorMeasurements;
    }

    /**
     * Add litter
     *
     * @param \AppBundle\Entity\Litter $litter
     *
     * @return Ram
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
     * @param Ewe|Ram $parent
     * @return Ram
     */
    public function setParent($parent)
    {
        parent::setParent($parent);
        return $this;
    }


    public static function getClassName() {
        return get_called_class();
    }


    /**
     * @return ArrayCollection
     */
    public function getEvents()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->orX(
                Criteria::expr()->eq('requestState', RequestStateType::FINISHED),
                Criteria::expr()->eq('requestState', RequestStateType::FINISHED_WITH_WARNING)
            ))
            ->orderBy(array("logDate" => Criteria::DESC))
        ;

        $declareBirths = [];

        /** @var Litter $litter */
        foreach ($this->litters as $litter) {//dump($litter->getChildren());die;
            foreach ($litter->getDeclareBirths() as $birth) {
                $declareBirths[] = $birth;
            }
        }

        return (new ArrayCollection(
            array_merge(
                parent::getEvents()->toArray(),
                $this->matings->toArray(),
                $declareBirths //TODO check if births are properly included
            )
        ))->matching($criteria);
    }
}
