<?php

namespace AppBundle\Entity;

use AppBundle\Constant\DeclareLogMessage;
use AppBundle\Enumerator\Language;
use AppBundle\Traits\EntityClassInfo;
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
 *     groups = {"ACTION_LOG_ADMIN","ACTION_LOG_USER","DECLARE","ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
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
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $logDate;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    protected $requestId;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
     */
    protected $messageId;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
     */
    protected $requestState;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 1)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    protected $action;

    /**
     *
     * @ORM\Column(type="string")
     * @Assert\Length(max = 1)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    protected $recoveryIndicator;


    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $relationNumberKeeper;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $ubn;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 15)
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $messageNumberToRecover;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $actionBy;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({"ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
     */
    protected $hideFailedMessage;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({"ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
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
     * DeclareBase constructor.
     */
    public function __construct() {
        $this->setHideFailedMessage(false);
        $this->hideForAdmin = false;
        $this->hideFailedMessage = false;
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
    }



    /**
     * @param int $language
     * @return string
     */
    function getDeclareLogMessage($language = Language::EN)
    {
        switch ($this::getShortClassName()) {
            case DeclareArrival::getShortClassName():
                return Language::getValue($language, DeclareLogMessage::ARRIVAL_REPORTED);
                break;

            case DeclareBirth::getShortClassName():
                return Language::getValue($language, DeclareLogMessage::BIRTH_REPORTED);
                break;

            case DeclareDepart::getShortClassName():
                return Language::getValue($language, DeclareLogMessage::DEPART_REPORTED);
                break;

            case DeclareExport::getShortClassName():
                return Language::getValue($language, DeclareLogMessage::EXPORT_REPORTED);
                break;

            case DeclareImport::getShortClassName():
                return Language::getValue($language, DeclareLogMessage::IMPORT_REPORTED);
                break;

            case DeclareLoss::getShortClassName():
                return Language::getValue($language, DeclareLogMessage::LOSS_REPORTED);
                break;

            case DeclareTagReplace::getShortClassName():
                return Language::getValue($language, DeclareLogMessage::TAG_REPLACE_REPORTED);
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



}
