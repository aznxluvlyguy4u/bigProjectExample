<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class RetrieveUbnDetailsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveUbnDetailsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveUbnDetailsResponse
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
     * @var arrayCollection
     */
    private $locations;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    private $actionBy;


    /**
     * RetrieveTagsResponse constructor.
     */
    public function __construct() {
        $this->setLogDate(new \DateTime());
        $this->locations = new ArrayCollection();
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
