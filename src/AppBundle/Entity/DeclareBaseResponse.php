<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\ErrorKindIndicator;
use AppBundle\Enumerator\SuccessIndicator;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DeclareBaseResponse
 *
 * @ORM\Table(name="declare_base_response")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBaseResponseRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * //TODO add new child classes to the DiscriminatorMap
 * @ORM\DiscriminatorMap(
 *   {
 *      "DeclareArrivalResponse" = "DeclareArrivalResponse",
 *      "DeclareDepartResponse" = "DeclareDepartResponse",
 *      "DeclareBirthResponse" = "DeclareBirthResponse",
 *      "DeclareLossResponse" = "DeclareLossResponse",
 *      "DeclareImportResponse" = "DeclareImportResponse",
 *      "DeclareExportResponse" = "DeclareExportResponse",
 *      "DeclareAnimalFlagResponse" = "DeclareAnimalFlagResponse",
 *      "DeclareTagsTransferResponse" = "DeclareTagsTransferResponse",
 *      "DeclareTagReplaceResponse" = "DeclareTagReplaceResponse",
 *      "RevokeDeclarationResponse" = "RevokeDeclarationResponse"
 *   }
 * )
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *      "DeclareArrivalResponse" : "AppBundle\Entity\DeclareArrivalResponse",
 *      "DeclareDepartResponse" : "AppBundle\Entity\DeclareDepartResponse",
 *      "DeclareBirthResponse" : "AppBundle\Entity\DeclareBirthResponse",
 *      "DeclareLossResponse" : "AppBundle\Entity\DeclareLossResponse",
 *      "DeclareImportResponse" : "AppBundle\Entity\DeclareImportResponse",
 *      "DeclareExportResponse" : "AppBundle\Entity\DeclareExportResponse",
 *      "DeclareAnimalFlagResponse" : "AppBundle\Entity\DeclareAnimalFlagResponse",
 *      "DeclareTagsTransferResponse" : "AppBundle\Entity\DeclareTagsTransferResponse",
 *      "DeclareTagReplaceResponse" : "AppBundle\Entity\DeclareTagReplaceResponse",
 *      "RevokeDeclarationResponse" : "AppBundle\Entity\RevokeDeclarationResponse"},
 *     groups = {
 *     "DECLARE",
 *     "RESPONSE_PERSISTENCE"
 * })
 *
 * @package AppBundle\Entity\DeclareBaseResponse
 */
abstract class DeclareBaseResponse
{
    use EntityClassInfo;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    protected $requestId;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $messageId;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 15)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    protected $messageNumber;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    protected $logDate;

    /**
     * @var string;
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $errorCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $errorMessage;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 1)
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $errorKindIndicator;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 1)
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $successIndicator;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $isRemovedByUser;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Person")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    protected $actionBy;

    /**
     * DeclareBaseResponse constructor.
     */
    public function __construct()
    {
        $this->logDate = new \DateTime();
        $this->setIsRemovedByUser(false);
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
     * Set messageId
     *
     * @param string $messageId
     *
     * @return DeclareBaseResponse
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
     * @return string
     */
    public function getMessageNumber()
    {
        return $this->messageNumber;
    }

    /**
     * @param string $messageNumber
     */
    public function setMessageNumber($messageNumber)
    {
        $this->messageNumber = $messageNumber;
    }

    /**
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return DeclareBaseResponse
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
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return DeclareBaseResponse
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
     * @return DeclareBaseResponse
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
     * @return DeclareBaseResponse
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
     * @return DeclareBaseResponse
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
     * Get requestId
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set requestId
     *
     * @param string $requestId
     *
     * @return DeclareBaseResponse
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
        $this->setMessageId($requestId);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isIsRemovedByUser()
    {
        return $this->isRemovedByUser;
    }

    /**
     * @param boolean $isRemovedByUser
     */
    public function setIsRemovedByUser($isRemovedByUser)
    {
        $this->isRemovedByUser = $isRemovedByUser;
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


    /**
     * @param DeclareBase $declareBase
     * @return DeclareBaseResponse
     */
    protected function setDeclareBaseValues(DeclareBase $declareBase)
    {
        $this->setActionBy($declareBase->getActionBy());
        $this->setRequestId($declareBase->getRequestId());
        $this->setMessageId($declareBase->getMessageId());
        return $this;
    }


    /**
     * @return DeclareBaseResponse
     */
    public function setSuccessValues()
    {
        $this->setSuccessIndicator(SuccessIndicator::J);
        $this->setErrorKindIndicator(null);
        $this->setErrorMessage(null);
        $this->setErrorCode(null);
        return $this;
    }


    /**
     * @param string $errorMessage
     * @param string $errorCode
     * @return DeclareBaseResponse
     */
    public function setFailedValues($errorMessage, $errorCode)
    {
        $this->setSuccessIndicator(SuccessIndicator::N);
        $this->setErrorKindIndicator(ErrorKindIndicator::F);
        $this->setErrorMessage($errorMessage);
        $this->setErrorCode($errorCode);
        return $this;
    }


    /**
     * @param string $errorMessage
     * @param string $errorCode
     * @return DeclareBaseResponse
     */
    public function setWarningValues($errorMessage, $errorCode)
    {
        $this->setSuccessIndicator(SuccessIndicator::J);
        $this->setErrorKindIndicator(ErrorKindIndicator::W);
        $this->setErrorMessage($errorMessage);
        $this->setErrorCode($errorCode);
        return $this;
    }
}
