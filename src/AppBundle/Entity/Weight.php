<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\WeightRepository")
 * @package AppBundle\Entity
 */
class Weight extends Measurement {

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $weight;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="weightMeasurements")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     */
    private $isBirthWeight;

   /**
    * Weight constructor.
    */
    public function __construct() 
    {
      parent::__construct();
        
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
}
