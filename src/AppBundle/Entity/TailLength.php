<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TailLengthRepository")
 * @package AppBundle\Entity
 */
class TailLength extends Measurement {

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $length;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="tailLengthMeasurements")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
    * TailLength constructor.
    */
    public function __construct() 
    {
      parent::__construct();
        
      $this->length = 0.00;
    }
    
    /**
     * Set tailLength
     *
     * @param float $length
     *
     * @return TailLength
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get tailLength
     *
     * @return float
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return TailLength
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
}
