<?php

namespace AppBundle\Entity;

use \AppBundle\Entity\Fat1;
use \AppBundle\Entity\Fat2;
use \AppBundle\Entity\Fat3;

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
     * @var Fat1
     *
     * @ORM\OneToOne(targetEntity="Fat1", inversedBy="bodyFat")
     * @ORM\JoinColumn(name="fat1_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Fat1")
     */
    private $fat1;

  /**
   * @var Fat2
   *
   * @ORM\OneToOne(targetEntity="Fat2", inversedBy="bodyFat")
   * @ORM\JoinColumn(name="fat2_id", referencedColumnName="id")
   * @JMS\Type("AppBundle\Entity\Fat2")
   */
    private $fat2;

    /**
     * @var Fat3
     *
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


    /**
     * @param mixed $bodyFat
     * @return bool
     */
    public function isEqualInValues($bodyFat)
    {
        if($bodyFat == null) {
            $isEqual = false;

        } else if($bodyFat instanceof BodyFat) {
            $isEqual = $this->fat1->getFat() == $bodyFat->fat1->getFat()
                && $this->fat2->getFat() == $bodyFat->fat2->getFat()
                && $this->fat3->getFat() == $bodyFat->fat3->getFat()
                && $this->getMeasurementDate() == $bodyFat->getMeasurementDate()
                && $this->getAnimal() == $bodyFat->getAnimal()
                && $this->getInspector() == $bodyFat->getInspector();

        } else {
            $isEqual = false;
        }

        return $isEqual;
    }


    /**
     * @param \DateTime $measurementDate
     * @param Animal $animal
     * @param Inspector $inspector
     * @param float $fat1Value
     * @param float $fat2Value
     * @param float $fat3Value
     * @return boolean
     */
    public function hasValues($measurementDate, $animal, $inspector, $fat1Value, $fat2Value, $fat3Value)
    {
        if($this->fat1 != null) { $thisFat1Value = $this->fat1->getFat(); } else { $thisFat1Value = null; }
        if($this->fat2 != null) { $thisFat2Value = $this->fat2->getFat(); } else { $thisFat2Value = null; }
        if($this->fat3 != null) { $thisFat3Value = $this->fat3->getFat(); } else { $thisFat3Value = null; }

        if($this->measurementDate == $measurementDate &&
            $this->animal == $animal &&
            $this->inspector == $inspector &&
            $thisFat1Value == $fat1Value &&
            $thisFat2Value == $fat2Value &&
            $thisFat3Value == $fat3Value
        ){
            return true;
        } else {
            return false;
        }
    }
}
