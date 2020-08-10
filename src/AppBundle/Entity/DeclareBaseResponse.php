<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\ErrorKindIndicator;
use AppBundle\Enumerator\SuccessIndicator;
use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareBaseResponse
 *
 * @ORM\Table(name="declare_base_response")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBaseResponseRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap(
 *   {
 *      "DeclareArrivalResponse" = "DeclareArrivalResponse",
 *      "DeclareDepartResponse" = "DeclareDepartResponse",
 *      "DeclareBirthResponse" = "DeclareBirthResponse",
 *      "DeclareLossResponse" = "DeclareLossResponse",
 *      "DeclareImportResponse" = "DeclareImportResponse",
 *      "DeclareExportResponse" = "DeclareExportResponse",
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
abstract class DeclareBaseResponse implements DeclareBaseResponseInterface
{
    use EntityClassInfo;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     */
    protected $id;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    protected $requestId;

    /**
     * @var string|null
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
     * @var string|null
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
     * @var DateTime|null
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
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $errorCode;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $errorMessage;

    /**
     * @var string|null
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
     * @var string|null
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
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
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
        $this->logDate = new DateTime();
        $this->isRemovedByUser = $this->isRemovedByUser ?? false;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setMessageId($messageId): DeclareBaseResponse
    {
        $this->messageId = $messageId;
        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getMessageNumber(): ?string
    {
        return $this->messageNumber;
    }

    public function setMessageNumber(?string $messageNumber): DeclareBaseResponseInterface
    {
        $this->messageNumber = $messageNumber;
        return $this;
    }

    public function setLogDate(DateTime $logDate): DeclareBaseResponse
    {
        $this->logDate = $logDate;
        return $this;
    }

    public function getLogDate(): DateTime
    {
        return $this->logDate;
    }

    public function setErrorCode(?string $errorCode): DeclareBaseResponseInterface
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorMessage(?string $errorMessage): DeclareBaseResponseInterface
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorKindIndicator($errorKindIndicator): DeclareBaseResponseInterface
    {
        $this->errorKindIndicator = $errorKindIndicator;
        return $this;
    }

    public function getErrorKindIndicator(): ?string
    {
        return $this->errorKindIndicator;
    }

    public function setSuccessIndicator($successIndicator): DeclareBaseResponseInterface
    {
        $this->successIndicator = $successIndicator;
        return $this;
    }

    public function getSuccessIndicator(): ?string
    {
        return $this->successIndicator;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): DeclareBaseResponseInterface
    {
        $this->requestId = $requestId;
        $this->setMessageId($requestId);
        return $this;
    }

    public function isIsRemovedByUser(): bool
    {
        return $this->isRemovedByUser;
    }

    public function setIsRemovedByUser(bool $isRemovedByUser): DeclareBaseResponseInterface
    {
        $this->isRemovedByUser = $isRemovedByUser;
        return $this;
    }

    public function getIsRemovedByUser(): bool
    {
        return $this->isIsRemovedByUser();
    }

    /**
     * @return Client|Employee|Person
     */
    public function getActionBy()
    {
        return $this->actionBy;
    }

    public function setActionBy($actionBy): DeclareBaseResponse
    {
        $this->actionBy = $actionBy;
        return $this;
    }

    protected function setDeclareBaseValues(DeclareBase $declareBase): DeclareBaseResponse
    {
        $this->setActionBy($declareBase->getActionBy());
        $this->setRequestId($declareBase->getRequestId());
        $this->setMessageId($declareBase->getMessageId());
        return $this;
    }

    public function setSuccessValues(): DeclareBaseResponseInterface
    {
        $this->setSuccessIndicator(SuccessIndicator::J);
        $this->setErrorKindIndicator(null);
        $this->setErrorMessage(null);
        $this->setErrorCode(null);
        return $this;
    }

    public function setFailedValues(string $errorMessage, string $errorCode): DeclareBaseResponseInterface
    {
        $this->setSuccessIndicator(SuccessIndicator::N);
        $this->setErrorKindIndicator(ErrorKindIndicator::F);
        $this->setErrorMessage($errorMessage);
        $this->setErrorCode($errorCode);
        return $this;
    }

    public function setWarningValues(string $errorMessage, string $errorCode): DeclareBaseResponseInterface
    {
        $this->setSuccessIndicator(SuccessIndicator::J);
        $this->setErrorKindIndicator(ErrorKindIndicator::W);
        $this->setErrorMessage($errorMessage);
        $this->setErrorCode($errorCode);
        return $this;
    }
}
