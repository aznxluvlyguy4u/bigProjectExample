<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Fat2
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Fat2Repository")
 * @package AppBundle\Entity
 */
class Fat2 extends Measurement {

  /**
   * @var float
   *
   * @ORM\Column(type="float")
   * @JMS\Type("float")
   * @Assert\NotBlank
   */
  private $fat;

  /**
   * @ORM\ManyToOne(targetEntity="Animal", inversedBy="fat2Measurements")
   * @JMS\Type("AppBundle\Entity\Animal")
   */
  private $animal;

  /**
   * Fat2 constructor.
   */
  public function __construct()
  {
    parent::__construct();

    $this->fat = 0.00;
  }

  /**
   * Set fat
   *
   * @param float $fat
   *
   * @return Fat2
   */
  public function setFat($fat)
  {
    $this->fat = $fat;

    return $this;
  }

  /**
   * Get fat
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
   * @param \AppBundle\Entity\Fat2 $animal
   *
   * @return Fat2
   */
  public function setAnimal(\AppBundle\Entity\Animal $animal = null)
  {
    $this->animal = $animal;

    return $this;
  }

  /**
   * Get animal
   *
   * @return \AppBundle\Entity\Fat2
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
   * @return Fat2
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
