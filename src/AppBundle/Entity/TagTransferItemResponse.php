<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Tag
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TagTransferItemResponseRepository")
 * @package AppBundle\Entity
 */
class TagTransferItemResponse {

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ubnNewOwner;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $relationNumberAcceptant;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $animalOrderNumber;

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
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 15)
     * @JMS\Type("string")
     */
    private $messageNumber;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     */
    private $isRemovedByUser;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $requestState;


    /*
     *
     */
    function __construct()
    {
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
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return TagTransferItemResponse
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
     * @return TagTransferItemResponse
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
     * Set ubnNewOwner
     *
     * @param string $ubnNewOwner
     *
     * @return TagTransferItemResponse
     */
    public function setUbnNewOwner($ubnNewOwner)
    {
        $this->ubnNewOwner = $ubnNewOwner;

        return $this;
    }

    /**
     * Get ubnNewOwner
     *
     * @return string
     */
    public function getUbnNewOwner()
    {
        return $this->ubnNewOwner;
    }

    /**
     * Set relationNumberAcceptant
     *
     * @param string $relationNumberAcceptant
     *
     * @return TagTransferItemResponse
     */
    public function setRelationNumberAcceptant($relationNumberAcceptant)
    {
        $this->relationNumberAcceptant = $relationNumberAcceptant;

        return $this;
    }

    /**
     * Get relationNumberAcceptant
     *
     * @return string
     */
    public function getRelationNumberAcceptant()
    {
        return $this->relationNumberAcceptant;
    }

    /**
     * Set animalOrderNumber
     *
     * @param string $animalOrderNumber
     *
     * @return TagTransferItemResponse
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
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return TagTransferItemResponse
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
     * @return TagTransferItemResponse
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
     * @return TagTransferItemResponse
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
     * @return TagTransferItemResponse
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
     * Set messageNumber
     *
     * @param string $messageNumber
     *
     * @return TagTransferItemResponse
     */
    public function setMessageNumber($messageNumber)
    {
        $this->messageNumber = $messageNumber;

        return $this;
    }

    /**
     * Get messageNumber
     *
     * @return string
     */
    public function getMessageNumber()
    {
        return $this->messageNumber;
    }

    /**
     * Set isRemovedByUser
     *
     * @param boolean $isRemovedByUser
     *
     * @return TagTransferItemResponse
     */
    public function setIsRemovedByUser($isRemovedByUser)
    {
        $this->isRemovedByUser = $isRemovedByUser;

        return $this;
    }

    /**
     * Get isRemovedByUser
     *
     * @return boolean
     */
    public function getIsRemovedByUser()
    {
        return $this->isRemovedByUser;
    }

    /**
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return TagTransferItemResponse
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
     * Set requestState
     *
     * @param string $requestState
     *
     * @return TagTransferItemResponse
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
}
