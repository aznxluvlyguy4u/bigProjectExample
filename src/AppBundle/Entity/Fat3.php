<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Fat3
 * @ORM\Entity(repositoryClass="AppBundle\Entity\FatRepository")
 * @package AppBundle\Entity
 */
class Fat3 extends Measurement {

  /**
   * @var float
   *
   * @ORM\Column(type="float", options={"default":0})
   * @JMS\Type("float")
   * @Assert\NotBlank
   */
  private $fat;

  /**
   * @ORM\OneToOne(targetEntity="BodyFat", mappedBy="fat3")
   * @JMS\Type("AppBundle\Entity\BodyFat")
   */
  private $bodyFat;

  /**
   * Fat3 constructor.
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
   * @return Fat3
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
   * Set inspector
   *
   * @param \AppBundle\Entity\Inspector $inspector
   *
   * @return Fat3
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
     * Set bodyFat
     *
     * @param \AppBundle\Entity\BodyFat $bodyFat
     *
     * @return Fat3
     */
    public function setBodyFat(\AppBundle\Entity\BodyFat $bodyFat = null)
    {
        $this->bodyFat = $bodyFat;

        return $this;
    }

    /**
     * Get bodyFat
     *
     * @return \AppBundle\Entity\BodyFat
     */
    public function getBodyFat()
    {
        return $this->bodyFat;
    }
}
