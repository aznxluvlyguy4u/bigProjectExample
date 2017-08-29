<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;

/**
 * Class RetrieveAnimalDetailsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalDetailsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimalDetailsResponse
{
    use EntityClassInfo;

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
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string")
     */
    private $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string")
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string")
     */
    private $animalOrderNumber;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $animalType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $animalHairColour;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $dateOfBirth;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $dateOfDeath;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $importDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $gender;

    /**
     * @var RetrieveAnimals
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="RetrieveAnimalDetails")
     * @ORM\JoinColumn(name="retrieve_animal_details_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\RetrieveAnimalDetails")
     */
    private $retrieveAnimalDetailsRequestMessage;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    private $actionBy;

    public function __construct() {
      $this->logDate = new \DateTime();
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
     * @return RetrieveAnimalDetailsResponse
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
     * @return RetrieveAnimalDetailsResponse
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
     * @return RetrieveAnimalDetailsResponse
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
     * @return RetrieveAnimalDetailsResponse
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
     * @return RetrieveAnimalDetailsResponse
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
     * @return RetrieveAnimalDetailsResponse
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
     * @return RetrieveAnimalDetailsResponse
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
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Get ulnCountryCode
     *
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * Set animalOrderNumber
     *
     * @param string $animalOrderNumber
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setAnimalOrderNumber($animalOrderNumber)
    {
        $this->animalOrderNumber = $animalOrderNumber;

        return $this;
    }

    /**
     * Get animalOrderNumber
     *
     * @return string
     */
    public function getAnimalOrderNumber()
    {
        return $this->animalOrderNumber;
    }

    /**
     * Set animalType
     *
     * @param integer $animalType
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setAnimalType($animalType)
    {
        $this->animalType = $animalType;

        return $this;
    }

    /**
     * Get animalType
     *
     * @return integer
     */
    public function getAnimalType()
    {
        return $this->animalType;
    }

    /**
     * Set animalHairColour
     *
     * @param string $animalHairColour
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setAnimalHairColour($animalHairColour)
    {
        $this->animalHairColour = $animalHairColour;

        return $this;
    }

    /**
     * Get animalHairColour
     *
     * @return string
     */
    public function getAnimalHairColour()
    {
        return $this->animalHairColour;
    }

    /**
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set dateOfDeath
     *
     * @param \DateTime $dateOfDeath
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setDateOfDeath($dateOfDeath)
    {
        $this->dateOfDeath = $dateOfDeath;

        return $this;
    }

    /**
     * Get dateOfDeath
     *
     * @return \DateTime
     */
    public function getDateOfDeath()
    {
        return $this->dateOfDeath;
    }

    /**
     * Set importDate
     *
     * @param \DateTime $importDate
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setImportDate($importDate)
    {
        $this->importDate = $importDate;

        return $this;
    }

    /**
     * Get importDate
     *
     * @return \DateTime
     */
    public function getImportDate()
    {
        return $this->importDate;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set retrieveAnimalDetailsRequestMessage
     *
     * @param \AppBundle\Entity\RetrieveAnimalDetails $retrieveAnimalDetailsRequestMessage
     *
     * @return RetrieveAnimalDetailsResponse
     */
    public function setRetrieveAnimalDetailsRequestMessage(\AppBundle\Entity\RetrieveAnimalDetails $retrieveAnimalDetailsRequestMessage = null)
    {
        $this->retrieveAnimalDetailsRequestMessage = $retrieveAnimalDetailsRequestMessage;

        return $this;
    }

    /**
     * Get retrieveAnimalDetailsRequestMessage
     *
     * @return \AppBundle\Entity\RetrieveAnimalDetails
     */
    public function getRetrieveAnimalDetailsRequestMessage()
    {
        return $this->retrieveAnimalDetailsRequestMessage;
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
