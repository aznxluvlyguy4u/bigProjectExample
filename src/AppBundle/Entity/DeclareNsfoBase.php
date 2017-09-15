<?php

namespace AppBundle\Entity;

use AppBundle\Component\MessageBuilderBase;
use AppBundle\Constant\DeclareLogMessage;
use AppBundle\Enumerator\Language;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class DeclareNsfoBase
 * @ORM\Table(name="declare_nsfo_base")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareNsfoBaseRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * //TODO add new child classes to the DiscriminatorMap
 * @ORM\DiscriminatorMap(
 *   {
 *      "Mate" = "Mate",
 *      "DeclareWeight" = "DeclareWeight",
 *      "Litter" = "Litter"
 *   }
 * )
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                        "Mate" : "AppBundle\Entity\Mate",
 *               "DeclareWeight" : "AppBundle\Entity\DeclareWeight",
 *                      "Litter" : "AppBundle\Entity\Litter"},
 *     groups = {"ERROR_DETAILS"})
 *
 * @package AppBundle\Entity\DeclareNsfoBase
 */
abstract class DeclareNsfoBase implements DeclareLogInterface
{
    use EntityClassInfo;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $logDate;

    /**
     * @var string
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Length(max = 20)
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
     */
    protected $messageId;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
     */
    protected $requestState;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 20)
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $relationNumberKeeper;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $ubn;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id", nullable=true)
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $actionBy;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="revoked_by_id", referencedColumnName="id", nullable=true)
     */
    protected $revokedBy;


    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    protected $revokeDate;


    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({"ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
     */
    protected $isHidden;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({"ERROR_DETAILS","ADMIN_HIDDEN_STATUS","HIDDEN_STATUS"})
     */
    protected $hideForAdmin;

    /**
     * This variable is used to differentiate between the current version,
     * and those that are overwritten by edits.
     * 
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $isOverwrittenVersion;

    /**
     * @var DeclareNsfoBase
     * @ORM\ManyToOne(targetEntity="DeclareNsfoBase", inversedBy="olderVersions")
     * @ORM\JoinColumn(name="newest_version_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\DeclareNsfoBase")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $newestVersion;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="DeclareNsfoBase", mappedBy="newestVersion")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\DeclareNsfoBase>")
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    protected $olderVersions;


    /**
     * DeclareNsfoBase constructor.
     */
    public function __construct() {
        $this->logDate = new \DateTime();
        $this->setMessageId(MessageBuilderBase::getNewRequestId());
        $this->isHidden = false;
        $this->hideForAdmin = false;
        $this->isOverwrittenVersion = false;
        $this->olderVersions = new ArrayCollection();
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
     * @return DeclareNsfoBase
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
     * Set messageId
     *
     * @param string $messageId
     *
     * @return DeclareNsfoBase
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
     * @return DeclareNsfoBase
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
     * Set relationNumberKeeper
     *
     * @param string $relationNumberKeeper
     *
     * @return DeclareNsfoBase
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
     * @return DeclareNsfoBase
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
     * @return Client|Employee|Person
     */
    public function getActionBy()
    {
        return $this->actionBy;
    }

    /**
     * @param Person $actionBy
     * @return DeclareNsfoBase
     */
    public function setActionBy($actionBy)
    {
        $this->actionBy = $actionBy;
        return $this;
    }

    /**
     * @return Person
     */
    public function getRevokedBy()
    {
        return $this->revokedBy;
    }

    /**
     * @param Person $revokedBy
     * @return DeclareNsfoBase
     */
    public function setRevokedBy($revokedBy)
    {
        $this->revokedBy = $revokedBy;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRevokeDate()
    {
        return $this->revokeDate;
    }

    /**
     * @param \DateTime $revokeDate
     * @return DeclareNsfoBase
     */
    public function setRevokeDate($revokeDate)
    {
        $this->revokeDate = $revokeDate;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     * @param boolean $isHidden
     * @return DeclareNsfoBase
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;
        return $this;
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
     * @return DeclareNsfoBase
     */
    public function setHideForAdmin($hideForAdmin)
    {
        $this->hideForAdmin = $hideForAdmin;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsOverwrittenVersion()
    {
        return $this->isOverwrittenVersion;
    }

    /**
     * @param boolean $isOverwrittenVersion
     * @return DeclareNsfoBase
     */
    public function setIsOverwrittenVersion($isOverwrittenVersion)
    {
        $this->isOverwrittenVersion = $isOverwrittenVersion;
        return $this;
    }

    /**
     * @return DeclareNsfoBase
     */
    public function getNewestVersion()
    {
        return $this->newestVersion;
    }

    /**
     * @param DeclareNsfoBase $newestVersion
     * @return DeclareNsfoBase
     */
    public function setNewestVersion($newestVersion)
    {
        $this->newestVersion = $newestVersion;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getOlderVersions()
    {
        return $this->olderVersions;
    }

    /**
     * @param ArrayCollection $olderVersions
     * @return DeclareNsfoBase
     */
    public function setOlderVersions($olderVersions)
    {
        $this->olderVersions = $olderVersions;
        return $this;
    }

    /**
     * @param DeclareNsfoBase $olderVersion
     * @return DeclareNsfoBase
     */
    public function addOlderVersion($olderVersion)
    {
        $this->olderVersions->add($olderVersion);
        return $this;
    }

    /**
     * @param DeclareNsfoBase $olderVersion
     * @return DeclareNsfoBase
     */
    public function removeOlderVersion($olderVersion)
    {
        $this->olderVersions->remove($olderVersion);
        return $this;
    }

    /**
     * @param Mate|DeclareWeight $nsfoMessage
     */
    protected function duplicateBaseValues($nsfoMessage)
    {
        //Note the messageId is not duplicated to keep each Declaration unique.

        //DeclareNsfoBase values
        $this->setLogDate($nsfoMessage->getLogDate());
        $this->setRequestState($nsfoMessage->getRequestState());
        $this->setRelationNumberKeeper($nsfoMessage->getRelationNumberKeeper());
        $this->setUbn($nsfoMessage->getUbn());
        $this->setActionBy($nsfoMessage->getActionBy());
        $this->setRevokedBy($nsfoMessage->getRevokedBy());
        $this->setRevokeDate($nsfoMessage->getRevokeDate());
        $this->setIsHidden($nsfoMessage->getIsHidden());
        $this->setIsOverwrittenVersion($nsfoMessage->getIsOverwrittenVersion());
        $this->setNewestVersion($nsfoMessage->getNewestVersion());
    }


    /**
     * @param int $language
     * @return string
     */
    function getDeclareLogMessage($language = Language::EN)
    {
        switch ($this::getShortClassName()) {
            case Mate::getShortClassName():
                return Language::getValue($language, DeclareLogMessage::MATING_REPORTED);
                break;
            case DeclareWeight::getShortClassName():
                /** @var DeclareWeight $this */
                return Language::getValue($language, DeclareLogMessage::WEIGHT_REPORTED). ' ' . strval($this->getWeight()) . ' Kg';
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
            case Mate::getShortClassName():
                /** @var Mate $this */
                if ($this->getStartDate()) {
                    $eventDateString .= $this->getStartDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                }
                if ($this->getEndDate()) {
                    if ($eventDateString !== '') {
                        $eventDateString .= ' - ';
                    }
                    $eventDateString .= $this->getEndDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT);
                }
                break;

            case DeclareWeight::getShortClassName():
                /** @var DeclareWeight $this */
                $eventDateString = $this->getMeasurementDate() ? $this->getMeasurementDate()->format(DeclareLogInterface::EVENT_DATE_FORMAT) : $eventDateString;
                break;
        }

        return $eventDateString !== '' ? $eventDateString : DeclareLogInterface::EVENT_DATE_NULL_RESPONSE;
    }


}
