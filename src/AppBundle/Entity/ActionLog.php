<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\UserActionType;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\Tests\Compiler\A;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ActionLog
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ActionLogRepository")
 */
class ActionLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({"ACTION_LOG_ADMIN","ACTION_LOG_USER"})
     */
    private $logDate;

    /**
     * In case of the user environment, this should be the Client for whom actions are done.
     *
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="user_account_id", referencedColumnName="id")
     * @JMS\Groups({"ACTION_LOG_ADMIN","ACTION_LOG_USER"})
     */
    private $userAccount;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     * @JMS\Groups({"ACTION_LOG_ADMIN","ACTION_LOG_USER"})
     */
    private $actionBy;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @JMS\Groups({"ACTION_LOG_ADMIN","ACTION_LOG_USER"})
     */
    private $userActionType;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"ACTION_LOG_ADMIN","ACTION_LOG_USER"})
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"ACTION_LOG_ADMIN","ACTION_LOG_USER"})
     */
    private $isCompleted;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"ACTION_LOG_ADMIN","ACTION_LOG_USER"})
     */
    private $isUserEnvironment;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":false}, nullable=false)
     * @JMS\Type("boolean")
     * @JMS\Groups({"ACTION_LOG_ADMIN","ACTION_LOG_USER"})
     */
    private $isRvoMessage;

    public function __construct($userAccount, $actionBy, $userActionType, $isCompleted = false, $description = null, $isUserEnvironment = true)
    {
        $this->logDate = new \DateTime();
        $this->isUserEnvironment = $isUserEnvironment;
        $this->userAccount = $userAccount;
        $this->actionBy = $actionBy;
        $this->userActionType = $userActionType;
        $this->isCompleted = $isCompleted;
        $this->description = $description;
        $this->isRvoMessage = ActionLog::isRvoMessageByUserActionType($userActionType);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    }

    /**
     * @return Person
     */
    public function getUserAccount()
    {
        return $this->userAccount;
    }

    /**
     * @param Person $userAccount
     */
    public function setUserAccount($userAccount)
    {
        $this->userAccount = $userAccount;
    }

    /**
     * @return Person
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
     * @return string
     */
    public function getUserActionType()
    {
        return $this->userActionType;
    }

    /**
     * @param string $userActionType
     */
    public function setUserActionType($userActionType)
    {
        $this->userActionType = $userActionType;
    }

    /**
     * @return boolean
     */
    public function isIsCompleted()
    {
        return $this->isCompleted;
    }

    /**
     * @param boolean $isCompleted
     */
    public function setIsCompleted($isCompleted)
    {
        $this->isCompleted = $isCompleted;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return boolean
     */
    public function isIsUserEnvironment()
    {
        return $this->isUserEnvironment;
    }

    /**
     * @param boolean $isUserEnvironment
     */
    public function setIsUserEnvironment($isUserEnvironment)
    {
        $this->isUserEnvironment = $isUserEnvironment;
    }

    /**
     * @return bool
     */
    public function isRvoMessage()
    {
        return $this->isRvoMessage;
    }

    /**
     * @param bool $isRvoMessage
     */
    public function setIsRvoMessage($isRvoMessage)
    {
        $this->isRvoMessage = $isRvoMessage;
    }


    /**
     * @param $userActionType
     * @return boolean
     */
    public static function isRvoMessageByUserActionType($userActionType)
    {
        return array_search($userActionType, UserActionType::getRvoMessageActionTypes()) !== false;
    }

}