<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

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
 * @package AppBundle\Entity\DeclareBase
 */
abstract class DeclareBase
{

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
     */
    protected $messageId;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
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
     */
    protected $relationNumberKeeper;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     */
    protected $ubn;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 15)
     * @JMS\Type("string")
     */
    protected $messageNumberToRecover;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    protected $actionBy;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    protected $hideFailedMessage;

    /**
     * DeclareBase constructor.
     */
    public function __construct() {
        $this->setHideFailedMessage(false);
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





}
