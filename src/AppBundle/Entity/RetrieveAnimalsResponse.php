<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimalsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimalsResponse
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var RetrieveAnimals
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="RetrieveAnimals")
     * @ORM\JoinColumn(name="retrieve_animals_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\RetrieveAnimals")
     */
    private $retrieveAnimalsRequestMessage;

    /**
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Animal")
     * @ORM\JoinTable(name="retrieve_animals_response_animals_retrieved",
     *      joinColumns={@ORM\JoinColumn(name="retrieve_animal_response_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="animal_id", referencedColumnName="id", unique=true)}
     * )
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animalsRetrieved;

    /**
     * @var Person
     *
     * @ORM\OneToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    private $actionBy;

    /**
     * RetrieveAnimalsResponse constructor.
     */
    public function __construct() {
      $this->animalsRetrieved = new ArrayCollection();
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
     * @return RetrieveAnimalsResponse
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
     * @return RetrieveAnimalsResponse
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
     * @return RetrieveAnimalsResponse
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
     * Set retrieveAnimalsRequestMessage
     *
     * @param \AppBundle\Entity\RetrieveAnimals $retrieveAnimalsRequestMessage
     *
     * @return RetrieveAnimalsResponse
     */
    public function setRetrieveAnimalsRequestMessage(\AppBundle\Entity\RetrieveAnimals $retrieveAnimalsRequestMessage = null)
    {
        $this->retrieveAnimalsRequestMessage = $retrieveAnimalsRequestMessage;

        return $this;
    }

    /**
     * Get retrieveAnimalsRequestMessage
     *
     * @return \AppBundle\Entity\RetrieveAnimals
     */
    public function getRetrieveAnimalsRequestMessage()
    {
        return $this->retrieveAnimalsRequestMessage;
    }

    /**
     * Add animalsRetrieved
     *
     * @param \AppBundle\Entity\Animal $animalsRetrieved
     *
     * @return RetrieveAnimalsResponse
     */
    public function addAnimalsRetrieved(\AppBundle\Entity\Animal $animalsRetrieved)
    {
        $this->animalsRetrieved[] = $animalsRetrieved;

        return $this;
    }

    /**
     * Remove animalsRetrieved
     *
     * @param \AppBundle\Entity\Animal $animalsRetrieved
     */
    public function removeAnimalsRetrieved(\AppBundle\Entity\Animal $animalsRetrieved)
    {
        $this->animalsRetrieved->removeElement($animalsRetrieved);
    }

    /**
     * Get animalsRetrieved
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnimalsRetrieved()
    {
        return $this->animalsRetrieved;
    }

    /**
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return RetrieveAnimalsResponse
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
     * @return RetrieveAnimalsResponse
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
     * @return RetrieveAnimalsResponse
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
     * @return RetrieveAnimalsResponse
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
     * @return Client|Employee
     */
    public function getActionBy()
    {
        return $this->actionBy;
    }

    /**
     * @param Person $actionBy
     */
    public function setActionBy($actionBy)
    {
        $this->actionBy = $actionBy;
    }
}
