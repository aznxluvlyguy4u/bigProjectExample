<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TailLengthRepository")
 * @package AppBundle\Entity
 */
class TailLength extends Measurement
{
    use EntityClassInfo;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $length;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="tailLengthMeasurements")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", onDelete="CASCADE")
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

    /**
     * Set inspector
     *
     * @param \AppBundle\Entity\Inspector $inspector
     *
     * @return TailLength
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
     * @param mixed $tailLength
     * @return bool
     */
    public function isEqualInValues($tailLength)
    {
        if($tailLength == null) {
            $isEqual = false;

        } else if($tailLength instanceof TailLength) {
            $isEqual = $this->getLength() == $tailLength->getLength()
                && $this->getMeasurementDate() == $tailLength->getMeasurementDate()
                && $this->getAnimal() == $tailLength->getAnimal()
                && $this->getInspector() == $tailLength->getInspector();

        } else {
            $isEqual = false;
        }

        return $isEqual;
    }
}
