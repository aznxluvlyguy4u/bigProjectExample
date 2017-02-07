<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveCountriesResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveCountriesResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveCountriesResponse
{

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

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
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Country")
     * @ORM\JoinTable(name="retrieve_countries_response_countries_retrieved",
     *      joinColumns={@ORM\JoinColumn(name="retrieve_countries_response_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="country_id", referencedColumnName="id", unique=true)}
     * )
     * @JMS\Type("AppBundle\Entity\Country")
     */
    private $countriesRetrieved;


    public function __construct() {
      $this->countriesRetrieved = new ArrayCollection();
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
     * @return RetrieveCountriesResponse
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
     * Set requestId
     *
     * @param string $requestId
     *
     * @return RetrieveCountriesResponse
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
     * @return RetrieveCountriesResponse
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
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return RetrieveCountriesResponse
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
     * @return RetrieveCountriesResponse
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
     * @return RetrieveCountriesResponse
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
     * @return RetrieveCountriesResponse
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
     * Add countriesRetrieved
     *
     * @param \AppBundle\Entity\Country $countriesRetrieved
     *
     * @return RetrieveCountriesResponse
     */
    public function addCountriesRetrieved(\AppBundle\Entity\Country $countriesRetrieved)
    {
        $this->countriesRetrieved[] = $countriesRetrieved;

        return $this;
    }

    /**
     * Remove countriesRetrieved
     *
     * @param \AppBundle\Entity\Country $countriesRetrieved
     */
    public function removeCountriesRetrieved(\AppBundle\Entity\Country $countriesRetrieved)
    {
        $this->countriesRetrieved->removeElement($countriesRetrieved);
    }

    /**
     * Get countriesRetrieved
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCountriesRetrieved()
    {
        return $this->countriesRetrieved;
    }
}
