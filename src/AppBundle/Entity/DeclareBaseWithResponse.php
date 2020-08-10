<?php

namespace AppBundle\Entity;

use AppBundle\Constant\DeclareLogMessage;
use AppBundle\Enumerator\ErrorKindIndicator;
use AppBundle\Enumerator\Language;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\SuccessIndicator;
use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareBaseWithResponse
 * @ORM\Table(name="declare_base_with_response")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBaseWithResponseRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap(
 *   {
 *      "DeclareAnimalFlag" = "DeclareAnimalFlag"
 *   }
 * )
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                      "DeclareAnimalFlag" : "AppBundle\Entity\DeclareAnimalFlag"
 * },
 *     groups = {
 *     "ACTION_LOG_ADMIN",
 *     "ACTION_LOG_USER",
 *     "BASIC",
 *     "DECLARE",
 *     "ERROR_DETAILS",
 *     "ADMIN_HIDDEN_STATUS",
 *     "HIDDEN_STATUS",
 *     "RESPONSE_PERSISTENCE",
 *     "RVO"
 * })
 *
 * @package AppBundle\Entity\DeclareBaseWithResponse
 */
abstract class DeclareBaseWithResponse implements DeclareLogInterface,DeclareBaseInterface,DeclareBaseResponseInterface
{
    use EntityClassInfo;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RESPONSE_PERSISTENCE",
     *     "RVO"
     * })
     */
    protected $id;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO"
     * })
     */
    protected $logDate;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE",
     *     "RVO"
     * })
     */
    protected $requestId;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ADMIN_HIDDEN_STATUS",
     *     "ERROR_DETAILS",
     *     "HIDDEN_STATUS",
     *     "TREATMENT",
     *     "RVO"
     * })
     */
    protected $requestState;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Length(max = 1)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    protected $action;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Length(max = 1)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    protected $recoveryIndicator;


    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO"
     * })
     */
    protected $relationNumberKeeper;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO"
     * })
     */
    protected $ubn;



    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 15)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO"
     * })
     */
    protected $messageNumberToRecover;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person", cascade={"refresh"})
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO"
     * })
     */
    protected $actionBy;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ADMIN_HIDDEN_STATUS",
     *     "ERROR_DETAILS",
     *     "HIDDEN_STATUS"
     * })
     */
    protected $hideFailedMessage;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ADMIN_HIDDEN_STATUS",
     *     "ERROR_DETAILS",
     *     "HIDDEN_STATUS"
     * })
     */
    protected $hideForAdmin;


    /**
     * @var DeclareBaseWithResponse|null
     * @ORM\ManyToOne(targetEntity="DeclareBaseWithResponse", cascade={"refresh"})
     * @ORM\JoinColumn(name="newest_version_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\DeclareBaseWithResponse")
     */
    protected $newestVersion;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ADMIN_HIDDEN_STATUS",
     *     "ERROR_DETAILS",
     *     "HIDDEN_STATUS"
     * })
     */
    protected $isRvoMessage;


    /*
     * RESPONSE VARIABLES
     */

    /**
     * Returned in RVO response
     *
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
    protected $responseLogDate;

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
     * DeclareBaseWithResponse constructor.
     */
    public function __construct() {
        $this->requestState = $this->requestState ?? RequestStateType::OPEN;
        $this->hideForAdmin = $this->hideForAdmin ?? false;
        $this->hideFailedMessage = $this->hideFailedMessage ?? false;
        $this->isRvoMessage = $this->isRvoMessage ?? true;
        $this->isRemovedByUser = $this->isRemovedByUser ?? false;
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
     * @param DateTime $logDate
     *
     * @return DeclareBaseWithResponse
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return DateTime
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
     * @return DeclareBaseWithResponse
     */
    public function setRequestId($requestId): DeclareBaseWithResponse
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Get requestId
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Legacy function, should be removed when possible.
     *
     * @param $messageId
     *
     * @return DeclareBaseWithResponse
     */
    public function setMessageId($messageId): DeclareBaseWithResponse
    {
        $this->setRequestId($messageId);

        return $this;
    }

    /**
     * Legacy function, should be removed when possible.
     *
     * @return string|void
     */
    public function getMessageId()
    {
        return $this->getRequestId();
    }


    /**
     * Set requestState
     *
     * @param string $requestState
     *
     * @return DeclareBaseWithResponse
     */
    public function setRequestState($requestState): DeclareBaseWithResponse
    {
        $this->requestState = $requestState;

        return $this;
    }

    /**
     * Get requestState
     *
     * @return string
     */
    public function getRequestState(): string
    {
        return $this->requestState;
    }

    /**
     * Set action
     *
     * @param string $action
     *
     * @return DeclareBaseWithResponse
     */
    public function setAction($action): DeclareBaseWithResponse
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Set recoveryIndicator
     *
     * @param string $recoveryIndicator
     *
     * @return DeclareBaseWithResponse
     */
    public function setRecoveryIndicator($recoveryIndicator): DeclareBaseWithResponse
    {
        $this->recoveryIndicator = $recoveryIndicator;

        return $this;
    }

    /**
     * Get recoveryIndicator
     *
     * @return string
     */
    public function getRecoveryIndicator(): string
    {
        return $this->recoveryIndicator;
    }

    /**
     * Set relationNumberKeeper
     *
     * @param string $relationNumberKeeper
     *
     * @return DeclareBaseWithResponse
     */
    public function setRelationNumberKeeper($relationNumberKeeper): DeclareBaseWithResponse
    {
        $this->relationNumberKeeper = $relationNumberKeeper;

        return $this;
    }

    /**
     * Get relationNumberKeeper
     *
     * @return string
     */
    public function getRelationNumberKeeper(): string
    {
        return $this->relationNumberKeeper;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareBaseWithResponse
     */
    public function setUbn($ubn): DeclareBaseWithResponse
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn(): string
    {
        return $this->ubn;
    }

    /**
     * @return string|null
     */
    public function getMessageNumberToRecover(): ?string
    {
        return $this->messageNumberToRecover;
    }

    /**
     * @param string $messageNumberToRecover
     * @return DeclareBaseWithResponse
     */
    public function setMessageNumberToRecover($messageNumberToRecover): DeclareBaseWithResponse
    {
        $this->messageNumberToRecover = $messageNumberToRecover;

        return $this;
    }

    /**
     * @return Client|Employee|Person
     */
    public function getActionBy()
    {
        return $this->actionBy;
    }

    /**
     * @param Person $actionBy
     * @return DeclareBaseWithResponse
     */
    public function setActionBy($actionBy): DeclareBaseWithResponse
    {
        $this->actionBy = $actionBy;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isHideFailedMessage(): bool
    {
        return $this->hideFailedMessage;
    }

    /**
     * @param boolean $hideFailedMessage
     * @return DeclareBaseWithResponse
     */
    public function setHideFailedMessage($hideFailedMessage): DeclareBaseWithResponse
    {
        $this->hideFailedMessage = $hideFailedMessage;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHideForAdmin(): bool
    {
        return $this->hideForAdmin;
    }

    /**
     * @param bool $hideForAdmin
     * @return DeclareBaseWithResponse
     */
    public function setHideForAdmin($hideForAdmin): DeclareBaseWithResponse
    {
        $this->hideForAdmin = $hideForAdmin;
        return $this;
    }

    /**
     * @return DeclareBaseWithResponse|null
     */
    public function getNewestVersion(): ?DeclareBaseWithResponse
    {
        return $this->newestVersion;
    }

    /**
     * @param DeclareBase $newestVersion
     * @return DeclareBaseWithResponse
     */
    public function setNewestVersion($newestVersion): DeclareBaseWithResponse
    {
        $this->newestVersion = $newestVersion;
        return $this;
    }


    /**
     * @return bool
     */
    public function isRvoMessage(): bool
    {
        return $this->isRvoMessage;
    }

    /**
     * @param bool $isRvoMessage
     * @return DeclareBaseWithResponse
     */
    public function setIsRvoMessage(bool $isRvoMessage): DeclareBaseWithResponse
    {
        $this->isRvoMessage = $isRvoMessage;
        return $this;
    }


    /**
     * @param int $language
     * @return string
     */
    function getDeclareLogMessage($language = Language::EN): string
    {
        switch ($this::getShortClassName()) {
            case DeclareAnimalFlag::getShortClassName():
                /** @var DeclareAnimalFlag $this */
                return Language::getValue($language, DeclareLogMessage::ANIMAL_FLAG_REPORTED)
                    .': '.$this->getFlagType();
                break;
        }
        return DeclareLogInterface::DECLARE_LOG_MESSAGE_NULL_RESPONSE;
    }


    /**
     * @return string
     */
    function getEventDate(): string
    {
        $eventDateString = '';

        switch ($this::getShortClassName())
        {
            case DeclareAnimalFlag::getShortClassName():
                /** @var DeclareAnimalFlag $this */
                $eventDateString = $this->getStartDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                if ($this->getEndDate()) {
                    $eventDateString .= ' - ' . $this->getEndDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                }
                break;
        }

        return $eventDateString !== '' ? $eventDateString : DeclareLogInterface::EVENT_DATE_NULL_RESPONSE;
    }


    public function setFinishedRequestState(): DeclareBaseWithResponse
    {
        $this->setRequestState(RequestStateType::FINISHED);

        return $this;
    }

    public function setFinishedWithWarningRequestState(): DeclareBaseWithResponse
    {
        $this->setRequestState(RequestStateType::FINISHED_WITH_WARNING);

        return $this;
    }

    public function setFailedRequestState(): DeclareBaseWithResponse
    {
        $this->setRequestState(RequestStateType::FAILED);

        return $this;
    }

    public function setRevokedRequestState(): DeclareBaseWithResponse
    {
        $this->setRequestState(RequestStateType::REVOKED);

        return $this;
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->getRequestState() === RequestStateType::REVOKED;
    }


    /*
     * RESPONSE GETTERS AND SETTERS
     */

    public function getResponseLogDate(): ?DateTime
    {
        return $this->responseLogDate;
    }

    public function setResponseLogDate(?DateTime $responseLogDate): DeclareBaseResponseInterface
    {
        $this->responseLogDate = $responseLogDate;
        return $this;
    }

    public function getMessageNumber(): ?string
    {
        return $this->messageNumber;
    }

    public function setMessageNumber(?string $messageNumber): DeclareBaseResponseInterface
    {
        $this->messageNumber = $messageNumber;
    }

    public function isIsRemovedByUser(): bool
    {
        return $this->isRemovedByUser;
    }

    public function setIsRemovedByUser($isRemovedByUser): DeclareBaseResponseInterface
    {
        $this->isRemovedByUser = $isRemovedByUser;

        return $this;
    }

    public function getIsRemovedByUser(): bool
    {
        return $this->isIsRemovedByUser();
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

    public function setErrorKindIndicator(?string $errorKindIndicator): DeclareBaseResponseInterface
    {
        $this->errorKindIndicator = $errorKindIndicator;

        return $this;
    }

    public function getErrorKindIndicator(): ?string
    {
        return $this->errorKindIndicator;
    }

    public function setSuccessIndicator(?string $successIndicator): DeclareBaseResponseInterface
    {
        $this->successIndicator = $successIndicator;

        return $this;
    }

    public function getSuccessIndicator(): ?string
    {
        return $this->successIndicator;
    }

    public function setSuccessValues(): DeclareBaseResponseInterface
    {
        $this->setRequestState(RequestStateType::FINISHED);
        $this->setSuccessIndicator(SuccessIndicator::J);
        $this->setErrorKindIndicator(null);
        $this->setErrorMessage(null);
        $this->setErrorCode(null);
        return $this;
    }

    public function setFailedValues($errorMessage, $errorCode): DeclareBaseResponseInterface
    {
        $this->setRequestState(RequestStateType::FAILED);
        $this->setSuccessIndicator(SuccessIndicator::N);
        $this->setErrorKindIndicator(ErrorKindIndicator::F);
        $this->setErrorMessage($errorMessage);
        $this->setErrorCode($errorCode);
        return $this;
    }

    public function setWarningValues($errorMessage, $errorCode): DeclareBaseResponseInterface
    {
        $this->setRequestState(RequestStateType::FINISHED_WITH_WARNING);
        $this->setSuccessIndicator(SuccessIndicator::J);
        $this->setErrorKindIndicator(ErrorKindIndicator::W);
        $this->setErrorMessage($errorMessage);
        $this->setErrorCode($errorCode);
        return $this;
    }
}
