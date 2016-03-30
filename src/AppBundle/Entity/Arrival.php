<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;

/**
 * Class Arrival
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ArrivalRepository")
 * @package AppBundle\Entity
 */
class Arrival {
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @ORM\Column(type="date")
   * @Assert\Date
   * @Assert\NotBlank
   * @JMS\Type("DateTime")
   */
  private $logDate;

  /**
   * @ORM\Column(type="date")
   * @Assert\Date
   * @Assert\NotBlank
   * @JMS\Type("DateTime")
   */
  private $arrivalDate;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 10)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $ubn;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @Assert\Length(max = 10)
   * @JMS\Type("string")
   */
  private $ubnPreviousOwner;

  /**
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="Animal", cascade={"persist"})
   * @JMS\Type("AppBundle\Entity\Animal")
   */
  private $animal;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $requestId;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $relationNumberKeeper;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 1)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $action;

  /**
   *
   * @ORM\Column(type="string")
   * @Assert\Length(max = 1)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $recoveryIndicator;

  /**
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="Location", cascade={"persist"})
   * @JMS\Type("AppBundle\Entity\Location")
   */
  private $location;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return Arrival
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * Set arrivalDate
     *
     * @param \DateTime $arrivalDate
     *
     * @return Arrival
     */
    public function setArrivalDate($arrivalDate)
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    /**
     * Get arrivalDate
     *
     * @return \DateTime
     */
    public function getArrivalDate()
    {
        return $this->arrivalDate;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return Arrival
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * Set ubnPreviousOwner
     *
     * @param string $ubnPreviousOwner
     *
     * @return Arrival
     */
    public function setUbnPreviousOwner($ubnPreviousOwner)
    {
        $this->ubnPreviousOwner = $ubnPreviousOwner;

        return $this;
    }

    /**
     * Get ubnPreviousOwner
     *
     * @return string
     */
    public function getUbnPreviousOwner()
    {
        return $this->ubnPreviousOwner;
    }

    /**
     * Set requestId
     *
     * @param string $requestId
     *
     * @return Arrival
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Get requestId
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set relationNumberKeeper
     *
     * @param string $relationNumberKeeper
     *
     * @return Arrival
     */
    public function setRelationNumberKeeper($relationNumberKeeper)
    {
        $this->relationNumberKeeper = $relationNumberKeeper;

        return $this;
    }

    /**
     * Get relationNumberKeeper
     *
     * @return string
     */
    public function getRelationNumberKeeper()
    {
        return $this->relationNumberKeeper;
    }

    /**
     * Set action
     *
     * @param string $action
     *
     * @return Arrival
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set recoveryIndicator
     *
     * @param string $recoveryIndicator
     *
     * @return Arrival
     */
    public function setRecoveryIndicator($recoveryIndicator)
    {
        $this->recoveryIndicator = $recoveryIndicator;

        return $this;
    }

    /**
     * Get recoveryIndicator
     *
     * @return string
     */
    public function getRecoveryIndicator()
    {
        return $this->recoveryIndicator;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return Arrival
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
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return Arrival
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }
}
