<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class PerformanceMeasurement
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PerformanceMeasurementRepository")
 * @package AppBundle\Entity
 */
class PerformanceMeasurement extends Measurement {
  
    /**
     * @ORM\ManyToMany(targetEntity="BodyFat")
     * @ORM\JoinTable(name="animal_bodyfat_measurements",
     *      joinColumns={@ORM\JoinColumn(name="performance_measurement_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="body_fat_id", referencedColumnName="id", unique=false)}
     *      )
     */
    private $bodyFatMeasurements;

    /**
     * @var MuscleThickness
     *
     * @ORM\ManyToOne(targetEntity="MuscleThickness", cascade={"persist"})
     * @ORM\JoinColumn(name="muscle_thickness_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\MuscleThickness")
     */
    private $muscleThickness;
  
    /**
     * @ORM\ManyToOne(targetEntity="Animal", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;
  
    /**
     * PerformanceMeasurement constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Add bodyFatMeasurement
     *
     * @param \AppBundle\Entity\BodyFat $bodyFatMeasurement
     *
     * @return PerformanceMeasurement
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
     * Set muscleThickness
     *
     * @param \AppBundle\Entity\MusleThickness $muscleThickness
     *
     * @return PerformanceMeasurement
     */
    public function setMuscleThickness(\AppBundle\Entity\MusleThickness $muscleThickness = null)
    {
        $this->muscleThickness = $muscleThickness;

        return $this;
    }

    /**
     * Get muscleThickness
     *
     * @return \AppBundle\Entity\MusleThickness
     */
    public function getMuscleThickness()
    {
        return $this->muscleThickness;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return PerformanceMeasurement
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set inspector
     *
     * @param \AppBundle\Entity\Inspector $inspector
     *
     * @return PerformanceMeasurement
     */
    public function setInspector(\AppBundle\Entity\Inspector $inspector = null)
    {
        $this->inspector = $inspector;

        return $this;
    }

    /**
     * Get inspector
     *
     * @return \AppBundle\Entity\Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }
}
