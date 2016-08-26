<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MuscleThicknessRepository")
 * @package AppBundle\Entity
 */
class MuscleThickness extends Measurement {

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $muscleThickness;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="muscleThicknessMeasurements")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

  /**
   * MuscleThickness constructor.
   */
    public function __construct() 
    {
      parent::__construct();
        
      $this->muscleThickness = 0.00;
    }

    /**
     * Set muscleThickness
     *
     * @param float $muscleThickness
     *
     * @return MuscleThickness
     */
    public function setMuscleThickness($muscleThickness)
    {
        $this->muscleThickness = $muscleThickness;

        return $this;
    }

    /**
     * Get muscleThickness
     *
     * @return float
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
     * @return MuscleThickness
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
     * @return MuscleThickness
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


    /**
     * @param mixed $muscleThickness
     * @return bool
     */
    public function isEqualInValues($muscleThickness)
    {
        if($muscleThickness == null) {
            $isEqual = false;

        } else if($muscleThickness instanceof MuscleThickness) {
            $isEqual = $this->getMuscleThickness() == $muscleThickness->getMuscleThickness()
                && $this->getMeasurementDate() == $muscleThickness->getMeasurementDate()
                && $this->getAnimal() == $muscleThickness->getAnimal()
                && $this->getInspector() == $muscleThickness->getInspector();

        } else {
            $isEqual = false;
        }

        return $isEqual;
    }
}
