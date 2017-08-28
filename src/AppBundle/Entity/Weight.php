<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\WeightRepository")
 * @package AppBundle\Entity
 */
class Weight extends Measurement
{
    use EntityClassInfo;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $weight;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="weightMeasurements")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean", options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isBirthWeight;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isRevoked;

   /**
    * Weight constructor.
    */
    public function __construct() 
    {
      parent::__construct();
        
      $this->isRevoked = false;  
      $this->weight = 0.00;
    }

    /**
     * Set weight
     *
     * @param float $weight
     *
     * @return Weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return Weight
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
     * @return boolean
     */
    public function isIsBirthWeight() {
      return $this->isBirthWeight;
    }

    /**
     * @param boolean $isBirthWeight
     */
    public function setIsBirthWeight($isBirthWeight) {
      $this->isBirthWeight = $isBirthWeight;
    }


    /**
     * Get isBirthWeight
     *
     * @return boolean
     */
    public function getIsBirthWeight()
    {
        return $this->isBirthWeight;
    }

    /**
     * Set inspector
     *
     * @param \AppBundle\Entity\Inspector $inspector
     *
     * @return Weight
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
     * @return boolean
     */
    public function isIsRevoked()
    {
        return $this->isRevoked;
    }

    /**
     * @return boolean
     */
    public function getIsRevoked()
    {
        return $this->isRevoked;
    }

    /**
     * @param boolean $isRevoked
     */
    public function setIsRevoked($isRevoked)
    {
        $this->isRevoked = $isRevoked;
    }
    
    
    /**
     * @param mixed $weight
     * @return bool
     */
    public function isEqualInValues($weight)
    {
        if($weight == null) {
            $isEqual = false;

        } else if($weight instanceof Weight) {
            $isEqual = $this->getWeight() == $weight->getWeight()
                && $this->getIsBirthWeight() == $weight->getIsBirthWeight()
                && $this->getMeasurementDate() == $weight->getMeasurementDate()
                && $this->getAnimal() == $weight->getAnimal()
                && $this->getInspector() == $weight->getInspector();

        } else {
            $isEqual = false;
        }

        return $isEqual;
    }
}
