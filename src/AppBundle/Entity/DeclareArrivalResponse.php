<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\DeclareArrival;

/**
 * Class DeclareArrivalResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareArrivalResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareArrivalResponse {

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="datetime", nullable=true)
   * @Assert\Date
   * @Assert\NotBlank
   * @JMS\Type("DateTime")
   */
  protected $date;

  /**
   * @var string;
   *
   * @ORM\Column(type="string", nullable=true)
   *
   */
  private $errorCode;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   */
  private $errorMessage;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @Assert\Length(max = 1)
   */
  private $errorKindIndicator;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @Assert\Length(max = 1)
   */
  private $successIndicator;

  /**
   * @var DeclareArrival
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareArrival", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareArrival")
   */
  private $declareArrivalRequestMessage;
//JColumn(name="declare_arrival_request_message_id", referencedColumnName="id")


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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return DeclareArrivalResponse
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return DeclareArrivalResponse
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set errorMessage
     *
     * @param string $errorMessage
     *
     * @return DeclareArrivalResponse
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * Get errorMessage
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Set errorKindIndicator
     *
     * @param string $errorKindIndicator
     *
     * @return DeclareArrivalResponse
     */
    public function setErrorKindIndicator($errorKindIndicator)
    {
        $this->errorKindIndicator = $errorKindIndicator;

        return $this;
    }

    /**
     * Get errorKindIndicator
     *
     * @return string
     */
    public function getErrorKindIndicator()
    {
        return $this->errorKindIndicator;
    }

    /**
     * Set successIndicator
     *
     * @param string $successIndicator
     *
     * @return DeclareArrivalResponse
     */
    public function setSuccessIndicator($successIndicator)
    {
        $this->successIndicator = $successIndicator;

        return $this;
    }

    /**
     * Get successIndicator
     *
     * @return string
     */
    public function getSuccessIndicator()
    {
        return $this->successIndicator;
    }

    /**
     * Set declareArrivalRequestMessage
     *
     * @param \AppBundle\Entity\DeclareArrival $declareArrivalRequestMessage
     *
     * @return DeclareArrivalResponse
     */
    public function setDeclareArrivalRequestMessage(\AppBundle\Entity\DeclareArrival $declareArrivalRequestMessage = null)
    {
        $this->declareArrivalRequestMessage = $declareArrivalRequestMessage;

        return $this;
    }

    /**
     * Get declareArrivalRequestMessage
     *
     * @return \AppBundle\Entity\DeclareArrival
     */
    public function getDeclareArrivalRequestMessage()
    {
        return $this->declareArrivalRequestMessage;
    }
}
