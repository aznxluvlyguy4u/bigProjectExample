<?php

namespace AppBundle\Entity;

use AppBundle\Component\MessageBuilderBase;
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
 *      "Mate" = "Mate"
 *   }
 * )
 * @package AppBundle\Entity\DeclareNsfoBase
 */
abstract class DeclareNsfoBase
{

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    protected $logDate;

    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    protected $messageId;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    protected $requestState;

    /**
     * @var string
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
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    protected $actionBy;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="revoked_by_id", referencedColumnName="id")
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
     */
    protected $isHidden;


    /**
     * This variable is used to differentiate between the current version,
     * and those that are overwritten by edits.
     * 
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    protected $isOverwrittenVersion;


    /**
     * DeclareNsfoBase constructor.
     */
    public function __construct() {
        $this->logDate = new \DateTime();
        $this->setMessageId(MessageBuilderBase::getNewRequestId());
        $this->isHidden = false;
        $this->isOverwrittenVersion = false;
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
     * @return Person
     */
    public function getRevokedBy()
    {
        return $this->revokedBy;
    }

    /**
     * @param Person $revokedBy
     */
    public function setRevokedBy($revokedBy)
    {
        $this->revokedBy = $revokedBy;
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
     */
    public function setRevokeDate($revokeDate)
    {
        $this->revokeDate = $revokeDate;
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
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;
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
     */
    public function setIsOverwrittenVersion($isOverwrittenVersion)
    {
        $this->isOverwrittenVersion = $isOverwrittenVersion;
    }





}
