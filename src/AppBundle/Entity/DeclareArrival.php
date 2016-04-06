<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareArrival
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareArrivalRepository")
 * @package AppBundle\Entity
 */
class DeclareArrival {
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @ORM\Column(type="datetime")
   * @Assert\Date
   * @Assert\NotBlank
   * @JMS\Type("DateTime")
   */
  private $logDate;

  /**
   * 2016-04-01T22:00:48.131Z
   *
   * @ORM\Column(type="datetime")
   * @Assert\Date
   * @Assert\NotBlank
   * @JMS\Type("DateTime")
   */
  private $arrivalDate;

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
  private $messageId;

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
   * @ORM\Column(type="boolean")
   * @JMS\Type("boolean")
   */
  private $importAnimal;

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $requestState;

  /**
   * @ORM\OneToMany(targetEntity="DeclareArrivalResponse", mappedBy="declareArrivalRequestMessage", cascade={"persist"})
   * @ORM\JoinColumn(name="declare_arrival_request_message_id", referencedColumnName="id")
   * @JMS\Type("array")
   */
  private $responses;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $relationNumberKeeper;

  /**
   * DeclareArrival constructor.
   */
  public function __construct() {
    //Create responses array
    $this->responses = new ArrayCollection();
  }

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
   * @return DeclareArrival
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
   * @return DeclareArrival
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
   * Set ubnPreviousOwner
   *
   * @param string $ubnPreviousOwner
   *
   * @return DeclareArrival
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
   * @return DeclareArrival
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
   * Set messageId
   *
   * @param string $messageId
   *
   * @return DeclareArrival
   */
  public function setMessageId($messageId)
  {
    $this->messageId = $messageId;

    return $this;
  }

  /**
   * Get messageId
   *
   * @return string
   */
  public function getMessageId()
  {
    return $this->messageId;
  }

  /**
   * Set action
   *
   * @param string $action
   *
   * @return DeclareArrival
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
   * @return DeclareArrival
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
   * Set importAnimal
   *
   * @param boolean $importAnimal
   *
   * @return DeclareArrival
   */
  public function setImportAnimal($importAnimal)
  {
    $this->importAnimal = $importAnimal;

    return $this;
  }

  /**
   * Get importAnimal
   *
   * @return boolean
   */
  public function getImportAnimal()
  {
    return $this->importAnimal;
  }

  /**
   * Set requestState
   *
   * @param string $requestState
   *
   * @return DeclareArrival
   */
  public function setRequestState($requestState)
  {
    $this->requestState = $requestState;

    return $this;
  }

  /**
   * Get requestState
   *
   * @return string
   */
  public function getRequestState()
  {
    return $this->requestState;
  }

  /**
   * Set animal
   *
   * @param \AppBundle\Entity\Animal $animal
   *
   * @return DeclareArrival
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
   * @return DeclareArrival
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

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareArrivalResponse $response
     *
     * @return DeclareArrival
     */
    public function addResponse(\AppBundle\Entity\DeclareArrivalResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareArrivalResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareArrivalResponse $response)
    {
        $this->responses->removeElement($response);
    }

    /**
     * Get responses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Set relationNumberKeeper
     *
     * @param string $relationNumberKeeper
     *
     * @return DeclareArrival
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
     * Set responses
     *
     * @param array $responses
     *
     * @return DeclareArrival
     */
    public function setResponses($responses)
    {
        $this->responses = $responses;

        return $this;
    }
}
