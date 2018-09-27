<?php

namespace AppBundle\Entity;

use AppBundle\Constant\DeclareLogMessage;
use AppBundle\Enumerator\Language;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\Translation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;

/**
 * Class DeclareBase
 * @ORM\Table(name="declare_base")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBaseRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * //TODO add new child classes to the DiscriminatorMap
 * @ORM\DiscriminatorMap(
 *   {
 *      "DeclarationDetail" = "DeclarationDetail",
 *      "DeclareAnimalFlag" = "DeclareAnimalFlag",
 *      "DeclareArrival" = "DeclareArrival",
 *      "DeclareBirth" = "DeclareBirth",
 *      "DeclareDepart" = "DeclareDepart",
 *      "DeclareExport" = "DeclareExport",
 *      "DeclareImport" = "DeclareImport",
 *      "DeclareLoss" = "DeclareLoss",
 *      "DeclareTagsTransfer" = "DeclareTagsTransfer",
 *      "DeclareTagReplace" = "DeclareTagReplace",
 *      "RevokeDeclaration" = "RevokeDeclaration"
 *   }
 * )
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                      "DeclarationDetail" : "AppBundle\Entity\DeclarationDetail",
 *                      "DeclareAnimalFlag" : "AppBundle\Entity\DeclareAnimalFlag",
 *                         "DeclareArrival" : "AppBundle\Entity\DeclareArrival",
 *                           "DeclareBirth" : "AppBundle\Entity\DeclareBirth",
 *                          "DeclareDepart" : "AppBundle\Entity\DeclareDepart",
 *                          "DeclareExport" : "AppBundle\Entity\DeclareExport",
 *                          "DeclareImport" : "AppBundle\Entity\DeclareImport",
 *                            "DeclareLoss" : "AppBundle\Entity\DeclareLoss",
 *                    "DeclareTagsTransfer" : "AppBundle\Entity\DeclareTagsTransfer",
 *                      "DeclareTagReplace" : "AppBundle\Entity\DeclareTagReplace",
 *                      "RevokeDeclaration" : "AppBundle\Entity\RevokeDeclaration"},
 *     groups = {
 *     "ACTION_LOG_ADMIN",
 *     "ACTION_LOG_USER",
 *     "BASIC",
 *     "DECLARE",
 *     "ERROR_DETAILS",
 *     "ADMIN_HIDDEN_STATUS",
 *     "HIDDEN_STATUS",
 *     "RVO"
 * })
 *
 * @package AppBundle\Entity\DeclareBase
 */
abstract class DeclareBase implements DeclareLogInterface
{
    use EntityClassInfo;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    protected $id;

    /**
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
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    protected $requestId;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ADMIN_HIDDEN_STATUS",
     *     "ERROR_DETAILS",
     *     "HIDDEN_STATUS",
     *     "RVO"
     * })
     */
    protected $messageId;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ADMIN_HIDDEN_STATUS",
     *     "ERROR_DETAILS",
     *     "HIDDEN_STATUS",
     *     "RVO"
     * })
     */
    protected $requestState;

    /**
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
     *
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
     *
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
     * @ORM\ManyToOne(targetEntity="Person")
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
     * @var DeclareBase
     * @ORM\ManyToOne(targetEntity="DeclareBase")
     * @ORM\JoinColumn(name="newest_version_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\DeclareBase")
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

    /**
     * DeclareBase constructor.
     */
    public function __construct() {
        $this->setHideFailedMessage(false);
        $this->hideForAdmin = false;
        $this->hideFailedMessage = false;
        $this->isRvoMessage = true;
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
     * @return DeclareBase
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
     * @return DeclareBase
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
        $this->messageId = $requestId;

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
     * @return DeclareBase
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
     * Set requestState
     *
     * @param string $requestState
     *
     * @return DeclareBase
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

    /**
     * Set action
     *
     * @param string $action
     *
     * @return DeclareBase
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set recoveryIndicator
     *
     * @param string $recoveryIndicator
     *
     * @return DeclareBase
     */
    public function setRecoveryIndicator($recoveryIndicator)
    {
        $this->recoveryIndicator = $recoveryIndicator;

        return $this;
    }

    /**
     * Get recoveryIndicator
     *
     * @return string
     */
    public function getRecoveryIndicator()
    {
        return $this->recoveryIndicator;
    }

    /**
     * Set relationNumberKeeper
     *
     * @param string $relationNumberKeeper
     *
     * @return DeclareBase
     */
    public function setRelationNumberKeeper($relationNumberKeeper)
    {
        $this->relationNumberKeeper = $relationNumberKeeper;

        return $this;
    }

    /**
     * Get relationNumberKeeper
     *
     * @return string
     */
    public function getRelationNumberKeeper()
    {
        return $this->relationNumberKeeper;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareBase
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * @return string
     */
    public function getMessageNumberToRecover()
    {
        return $this->messageNumberToRecover;
    }

    /**
     * @param string $messageNumberToRecover
     */
    public function setMessageNumberToRecover($messageNumberToRecover)
    {
        $this->messageNumberToRecover = $messageNumberToRecover;
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
     */
    public function setActionBy($actionBy)
    {
        $this->actionBy = $actionBy;
    }

    /**
     * @return boolean
     */
    public function isHideFailedMessage()
    {
        return $this->hideFailedMessage;
    }

    /**
     * @param boolean $hideFailedMessage
     */
    public function setHideFailedMessage($hideFailedMessage)
    {
        $this->hideFailedMessage = $hideFailedMessage;
    }

    /**
     * @return bool
     */
    public function isHideForAdmin()
    {
        return $this->hideForAdmin;
    }

    /**
     * @param bool $hideForAdmin
     */
    public function setHideForAdmin($hideForAdmin)
    {
        $this->hideForAdmin = $hideForAdmin;
    }

    /**
     * @return DeclareBase
     */
    public function getNewestVersion()
    {
        return $this->newestVersion;
    }

    /**
     * @param DeclareBase $newestVersion
     * @return DeclareBase
     */
    public function setNewestVersion($newestVersion)
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
     * @return DeclareBase
     */
    public function setIsRvoMessage(bool $isRvoMessage): DeclareBase
    {
        $this->isRvoMessage = $isRvoMessage;
        return $this;
    }


    /**
     * @param int $language
     * @return string
     */
    function getDeclareLogMessage($language = Language::EN)
    {
        switch ($this::getShortClassName()) {
            case DeclareArrival::getShortClassName():
                /** @var DeclareArrival $this */
                return
                    Language::getValue($language, DeclareLogMessage::ARRIVAL_REPORTED)
                    . ' ('.Language::getValue($language, Language::PREVIOUS_OWNER).': '.$this->getUbnPreviousOwner().')';
                break;

            case DeclareBirth::getShortClassName():
                /** @var DeclareBirth $this */
                return Language::getValue($language, DeclareLogMessage::BIRTH_REPORTED)
                    . ' ('.Language::getValue($language, strtolower($this->getGender())).' '.$this->getUln().')';
                break;

            case DeclareDepart::getShortClassName():
                /** @var DeclareDepart $this */
                return Language::getValue($language, DeclareLogMessage::DEPART_REPORTED)
                    . ' ('.Language::getValue($language, Language::NEW_OWNER).': '.$this->getUbnNewOwner().')';
                break;

            case DeclareExport::getShortClassName():
                /** @var DeclareExport $this */
                return Language::getValue($language, DeclareLogMessage::EXPORT_REPORTED);
                break;

            case DeclareImport::getShortClassName():
                /** @var DeclareImport $this */
                return Language::getValue($language, DeclareLogMessage::IMPORT_REPORTED);
                break;

            case DeclareLoss::getShortClassName():
                /** @var DeclareLoss $this */
                return Language::getValue($language, DeclareLogMessage::LOSS_REPORTED);
                break;

            case DeclareTagReplace::getShortClassName():
                /** @var DeclareTagReplace $this */
                return Language::getValue($language, DeclareLogMessage::TAG_REPLACE_REPORTED)
                    . ' ('.Language::getValue($language, Language::OLD_TAG).': '.$this->getUlnToReplace().')';
                break;
        }
        return DeclareLogInterface::DECLARE_LOG_MESSAGE_NULL_RESPONSE;
    }


    /**
     * @return string
     */
    function getEventDate()
    {
        $eventDateString = '';

        switch ($this::getShortClassName())
        {
            case DeclareArrival::getShortClassName():
                /** @var DeclareArrival $this */
                return $this->getArrivalDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                break;

            case DeclareBirth::getShortClassName():
                /** @var DeclareBirth $this */
                return $this->getDateOfBirth()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                break;

            case DeclareDepart::getShortClassName():
                /** @var DeclareDepart $this */
                return $this->getDepartDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                break;

            case DeclareExport::getShortClassName():
                /** @var DeclareExport $this */
                return $this->getExportDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                break;

            case DeclareImport::getShortClassName():
                /** @var DeclareImport $this */
                return $this->getImportDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                break;

            case DeclareLoss::getShortClassName():
                /** @var DeclareLoss $this */
                return $this->getDateOfDeath()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                break;

            case DeclareTagReplace::getShortClassName():
                /** @var DeclareTagReplace $this */
                return $this->getReplaceDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                break;
        }

        return $eventDateString !== '' ? $eventDateString : DeclareLogInterface::EVENT_DATE_NULL_RESPONSE;
    }


    public function setFinishedRequestState()
    {
        $this->setRequestState(RequestStateType::FINISHED);
    }

    public function setFinishedWithWarningRequestState()
    {
        $this->setRequestState(RequestStateType::FINISHED_WITH_WARNING);
    }

    public function setFailedRequestState()
    {
        $this->setRequestState(RequestStateType::FAILED);
    }

    public function setRevokedRequestState()
    {
        $this->setRequestState(RequestStateType::REVOKED);
    }
}
