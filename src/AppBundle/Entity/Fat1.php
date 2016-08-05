<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Fat1
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Fat1Repository")
 * @package AppBundle\Entity
 */
class Fat1 extends Measurement {

  /**
   * @var float
   *
   * @ORM\Column(type="float")
   * @JMS\Type("float")
   * @Assert\NotBlank
   */
  private $fat;

  /**
   * @ORM\ManyToOne(targetEntity="Animal", inversedBy="fat1Measurements")
   * @JMS\Type("AppBundle\Entity\Animal")
   */
  private $animal;

  /**
   * Fat1 constructor.
   */
  public function __construct()
  {
    parent::__construct();

    $this->fat = 0.00;
  }

  /**
   * Set Fat1
   *
   * @param float $fat
   *
   * @return Fat1
   */
  public function setFat($fat)
  {
    $this->fat = $fat;

    return $this;
  }

  /**
   * Get Fat1
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
   * @param \AppBundle\Entity\Fat1 $animal
   *
   * @return Fat1
   */
  public function setAnimal(\AppBundle\Entity\Animal $animal = null)
  {
    $this->animal = $animal;

    return $this;
  }

  /**
   * Get animal
   *
   * @return \AppBundle\Entity\Fat1
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
   * @return Fat1
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
