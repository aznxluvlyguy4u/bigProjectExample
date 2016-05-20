<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class RetrieveUBNDetailsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveUBNDetailsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveUBNDetailsResponse
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
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $ubnNumber;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $ubnStreetName;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $ubnPostalCode;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $ubnCity;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $companyType;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $companyHolderName;

    /**
     * RetrieveTagsResponse constructor.
     */
    public function __construct() {
      $this->setLogDate(new \DateTime());
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
     * @return RetrieveTagsResponse
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
     * @return RetrieveTagsResponse
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
     * @return RetrieveTagsResponse
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
     * @return RetrieveTagsResponse
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
     * @return RetrieveTagsResponse
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
     * @return RetrieveTagsResponse
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
     * @return RetrieveTagsResponse
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
     * Set ubnNumber
     *
     * @param string $ubnNumber
     *
     * @return RetrieveUBNDetailsResponse
     */
    public function setUbnNumber($ubnNumber)
    {
        $this->ubnNumber = $ubnNumber;

        return $this;
    }

    /**
     * Get ubnNumber
     *
     * @return string
     */
    public function getUbnNumber()
    {
        return $this->ubnNumber;
    }

    /**
     * Set ubnStreetName
     *
     * @param string $ubnStreetName
     *
     * @return RetrieveUBNDetailsResponse
     */
    public function setUbnStreetName($ubnStreetName)
    {
        $this->ubnStreetName = $ubnStreetName;

        return $this;
    }

    /**
     * Get ubnStreetName
     *
     * @return string
     */
    public function getUbnStreetName()
    {
        return $this->ubnStreetName;
    }

    /**
     * Set ubnPostalCode
     *
     * @param string $ubnPostalCode
     *
     * @return RetrieveUBNDetailsResponse
     */
    public function setUbnPostalCode($ubnPostalCode)
    {
        $this->ubnPostalCode = $ubnPostalCode;

        return $this;
    }

    /**
     * Get ubnPostalCode
     *
     * @return string
     */
    public function getUbnPostalCode()
    {
        return $this->ubnPostalCode;
    }

    /**
     * Set ubnCity
     *
     * @param string $ubnCity
     *
     * @return RetrieveUBNDetailsResponse
     */
    public function setUbnCity($ubnCity)
    {
        $this->ubnCity = $ubnCity;

        return $this;
    }

    /**
     * Get ubnCity
     *
     * @return string
     */
    public function getUbnCity()
    {
        return $this->ubnCity;
    }

    /**
     * Set companyType
     *
     * @param string $companyType
     *
     * @return RetrieveUBNDetailsResponse
     */
    public function setCompanyType($companyType)
    {
        $this->companyType = $companyType;

        return $this;
    }

    /**
     * Get companyType
     *
     * @return string
     */
    public function getCompanyType()
    {
        return $this->companyType;
    }

    /**
     * Set companyHolderName
     *
     * @param string $companyHolderName
     *
     * @return RetrieveUBNDetailsResponse
     */
    public function setCompanyHolderName($companyHolderName)
    {
        $this->companyHolderName = $companyHolderName;

        return $this;
    }

    /**
     * Get companyHolderName
     *
     * @return string
     */
    public function getCompanyHolderName()
    {
        return $this->companyHolderName;
    }
}
