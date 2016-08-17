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
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="bodyFatMeasurements")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @ORM\OneToOne(targetEntity="Fat1", inversedBy="bodyFat")
     * @ORM\JoinColumn(name="fat1_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Fat1")
     */
    private $fat1;

  /**
   * @ORM\OneToOne(targetEntity="Fat2", inversedBy="bodyFat")
   * @ORM\JoinColumn(name="fat2_id", referencedColumnName="id")
   * @JMS\Type("AppBundle\Entity\Fat2")
   */
    private $fat2;

    /**
     * @ORM\OneToOne(targetEntity="Fat3", inversedBy="bodyFat")
     * @ORM\JoinColumn(name="fat3_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Fat3")
     */
    private $fat3;

    /**
    * BodyFat constructor.
    */
    public function __construct()
    {
      parent::__construct();

    }

    /**
     * Set animal
     *
     * @param Animal $animal
     *
     * @return BodyFat
     */
    public function setAnimal(Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set inspector
     *
     * @param Inspector $inspector
     *
     * @return BodyFat
     */
    public function setInspector(Inspector $inspector = null)
    {
        $this->inspector = $inspector;

        return $this;
    }

    /**
     * Get inspector
     *
     * @return Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    /**
     * Set fat1
     *
     * @param Fat1 $fat1
     *
     * @return BodyFat
     */
    public function setFat1(Fat1 $fat1 = null)
    {
        $this->fat1 = $fat1;

        return $this;
    }

    /**
     * Get fat1
     *
     * @return Fat1
     */
    public function getFat1()
    {
        return $this->fat1;
    }

    /**
     * Set fat2
     *
     * @param Fat2 $fat2
     *
     * @return BodyFat
     */
    public function setFat2(Fat2 $fat2 = null)
    {
        $this->fat2 = $fat2;

        return $this;
    }

    /**
     * Get fat2
     *
     * @return Fat2
     */
    public function getFat2()
    {
        return $this->fat2;
    }

    /**
     * Set fat3
     *
     * @param Fat3 $fat3
     *
     * @return BodyFat
     */
    public function setFat3(Fat3 $fat3 = null)
    {
        $this->fat3 = $fat3;

        return $this;
    }

    /**
     * Get fat3
     *
     * @return Fat3
     */
    public function getFat3()
    {
        return $this->fat3;
    }
}
