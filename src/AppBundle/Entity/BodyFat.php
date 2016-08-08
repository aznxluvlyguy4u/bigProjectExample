<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BodyFatRepository")
 * @package AppBundle\Entity
 */
class BodyFat extends Measurement {

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $fat;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="bodyFatMeasurements")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
    * BodyFat constructor.
    */
    public function __construct()
    {
      parent::__construct();
      
      $this->fat = 0.00;
    }

    /**
     * Set bodyFat
     *
     * @param float $fat
     *
     * @return BodyFat
     */
    public function setFat($fat)
    {
        $this->fat = $fat;

        return $this;
    }

    /**
     * Get bodyFat
     *
     * @return float
     */
    public function getFat()
    {
        return $this->fat;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\BodyFat $animal
     *
     * @return BodyFat
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\BodyFat
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
     * @return BodyFat
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
