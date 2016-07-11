<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\AnimalType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Neuter
 * @ORM\Entity(repositoryClass="AppBundle\Entity\NeuterRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class Neuter extends Animal
{
    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentNeuter")
     * @JMS\Type("AppBundle\Entity\Neuter")
     */
    protected $children;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $objectType;

    /**
     * Neuter constructor.
     */
    public function __construct() {
        //Call super constructor first
        parent::__construct();

        $this->objectType = "Neuter";
        $this->setAnimalType(AnimalType::sheep);
        $this->setAnimalCategory(3);

        //Create children array
        $this->children = new ArrayCollection();
    }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Neuter
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
     * @return Neuter
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
     * Set isAlive
     *
     * @param boolean $isAlive
     *
     * @return Neuter
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
     * @return Neuter
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
     * @return Neuter
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
     * @return Neuter
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
     * @return Neuter
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
     * @return Neuter
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
     * @return Neuter
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
    public function getFlags() {
        return $this->flags;
    }



    /**
     * Add weightMeasurement
     *
     * @param \AppBundle\Entity\WeightMeasurement $weightMeasurement
     *
     * @return Neuter
     */
    public function addWeightMeasurement(\AppBundle\Entity\WeightMeasurement $weightMeasurement)
    {
        $this->weightMeasurements[] = $weightMeasurement;

        return $this;
    }

    /**
     * Remove weightMeasurement
     *
     * @param \AppBundle\Entity\WeightMeasurement $weightMeasurement
     */
    public function removeWeightMeasurement(\AppBundle\Entity\WeightMeasurement $weightMeasurement)
    {
        $this->weightMeasurements->removeElement($weightMeasurement);
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
     * @return Neuter
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
     * Add bodyFatMeasurement
     *
     * @param \AppBundle\Entity\BodyFat $bodyFatMeasurement
     *
     * @return Neuter
     */
    public function addBodyFatMeasurement(\AppBundle\Entity\BodyFat $bodyFatMeasurement)
    {
        $this->bodyFatMeasurements[] = $bodyFatMeasurement;

        return $this;
    }

    /**
     * Remove bodyFatMeasurement
     *
     * @param \AppBundle\Entity\BodyFat $bodyFatMeasurement
     */
    public function removeBodyFatMeasurement(\AppBundle\Entity\BodyFat $bodyFatMeasurement)
    {
        $this->bodyFatMeasurements->removeElement($bodyFatMeasurement);
    }

    /**
     * Get bodyFatMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBodyFatMeasurements()
    {
        return $this->bodyFatMeasurements;
    }

    /**
     * Add muscleThicknessMeasurement
     *
     * @param \AppBundle\Entity\MuscleThickness $muscleThicknessMeasurement
     *
     * @return Neuter
     */
    public function addMuscleThicknessMeasurement(\AppBundle\Entity\MuscleThickness $muscleThicknessMeasurement)
    {
        $this->muscleThicknessMeasurements[] = $muscleThicknessMeasurement;

        return $this;
    }

    /**
     * Remove muscleThicknessMeasurement
     *
     * @param \AppBundle\Entity\MuscleThickness $muscleThicknessMeasurement
     */
    public function removeMuscleThicknessMeasurement(\AppBundle\Entity\MuscleThickness $muscleThicknessMeasurement)
    {
        $this->muscleThicknessMeasurements->removeElement($muscleThicknessMeasurement);
    }

    /**
     * Get muscleThicknessMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMuscleThicknessMeasurements()
    {
        return $this->muscleThicknessMeasurements;
    }

    /**
     * Add tailLengthMeasurement
     *
     * @param \AppBundle\Entity\TailLength $tailLengthMeasurement
     *
     * @return Neuter
     */
    public function addTailLengthMeasurement(\AppBundle\Entity\TailLength $tailLengthMeasurement)
    {
        $this->tailLengthMeasurements[] = $tailLengthMeasurement;

        return $this;
    }

    /**
     * Remove tailLengthMeasurement
     *
     * @param \AppBundle\Entity\TailLength $tailLengthMeasurement
     */
    public function removeTailLengthMeasurement(\AppBundle\Entity\TailLength $tailLengthMeasurement)
    {
        $this->tailLengthMeasurements->removeElement($tailLengthMeasurement);
    }

    /**
     * Get tailLengthMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTailLengthMeasurements()
    {
        return $this->tailLengthMeasurements;
    }
}
