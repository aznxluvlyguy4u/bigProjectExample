<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\AnimalType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimals
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalsRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimals
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $requestId;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $messageId;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $requestState;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $relationNumberKeeper;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     */
    private $ubn;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     */
    private $animalType;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    private $actionBy;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Assert\NotBlank
     * @JMS\Type("boolean")
     */
    private $isRvoLeading;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $currentAnimalsCount;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $retrievedAnimalsCount;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $newAnimalsCount;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $blockedNewAnimalsCount;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $removedAnimalsCount;

    /**
     * RetrieveAnimals constructor
     */
    public function __construct() {
        $this->logDate = new \DateTime();
        $this->animalType = AnimalType::sheep;
        $this->isRvoLeading = false;
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
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return RetrieveAnimals
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;
        $this->setUbn($location->getUbn());

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set animalType
     *
     * @param integer $animalType
     *
     * @return RetrieveAnimals
     */
    public function setAnimalType($animalType)
    {
        $this->animalType = $animalType;

        return $this;
    }

    /**
     * Get animalType
     *
     * @return integer
     */
    public function getAnimalType()
    {
        return $this->animalType;
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
     * @return $this
     */
    public function setActionBy($actionBy)
    {
        $this->actionBy = $actionBy;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRvoLeading()
    {
        return $this->isRvoLeading;
    }

    /**
     * @param bool $isRvoLeading
     * @return RetrieveAnimals
     */
    public function setIsRvoLeading($isRvoLeading)
    {
        $this->isRvoLeading = $isRvoLeading;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentAnimalsCount()
    {
        return $this->currentAnimalsCount;
    }

    /**
     * @param int $currentAnimalsCount
     * @return RetrieveAnimals
     */
    public function setCurrentAnimalsCount($currentAnimalsCount)
    {
        $this->currentAnimalsCount = $currentAnimalsCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getRetrievedAnimalsCount()
    {
        return $this->retrievedAnimalsCount;
    }

    /**
     * @param int $retrievedAnimalsCount
     * @return RetrieveAnimals
     */
    public function setRetrievedAnimalsCount($retrievedAnimalsCount)
    {
        $this->retrievedAnimalsCount = $retrievedAnimalsCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getNewAnimalsCount()
    {
        return $this->newAnimalsCount;
    }

    /**
     * @param int $newAnimalsCount
     * @return RetrieveAnimals
     */
    public function setNewAnimalsCount($newAnimalsCount)
    {
        $this->newAnimalsCount = $newAnimalsCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockedNewAnimalsCount()
    {
        return $this->blockedNewAnimalsCount;
    }

    /**
     * @param int $blockedNewAnimalsCount
     * @return RetrieveAnimals
     */
    public function setBlockedNewAnimalsCount($blockedNewAnimalsCount)
    {
        $this->blockedNewAnimalsCount = $blockedNewAnimalsCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getRemovedAnimalsCount()
    {
        return $this->removedAnimalsCount;
    }

    /**
     * @param int $removedAnimalsCount
     * @return RetrieveAnimals
     */
    public function setRemovedAnimalsCount($removedAnimalsCount)
    {
        $this->removedAnimalsCount = $removedAnimalsCount;
        return $this;
    }


}
