<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RetrieveTags
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveTagsRepository")
 * @package AppBundle\Entity
 */
class RetrieveTags implements BasicRetrieveRvoDeclareInterface
{
    use EntityClassInfo;

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
     * @JMS\Groups({
     *     "MINIMAL",
     *     "RVO"
     * })
     */
    private $logDate;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $requestId;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $messageId;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "MINIMAL",
     *     "RVO"
     * })
     */
    private $requestState;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $relationNumberKeeper;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $ubn;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $tagType;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $location;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $animalType;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     * @JMS\Groups({
     *     "BASIC",
     *     "MINIMAL",
     *     "RVO"
     * })
     */
    private $actionBy;

    /**
     * Is the retrieveTag manually initiated by the user
     *
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Assert\NotBlank
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "BASIC",
     *     "MINIMAL",
     *     "RVO"
     * })
     */
    private $isManual;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Assert\NotBlank
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "BASIC",
     *     "MINIMAL",
     *     "RVO"
     * })
     */
    private $hasForceDeleteAnimalsFailed;

    public function __construct() {
        $this->setLogDate(new \DateTime());
        $this->isManual = false;
        $this->hasForceDeleteAnimalsFailed = false;
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
     * @return RetrieveTags
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
     * @return RetrieveTags
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
        $this->setMessageId($requestId);
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
     * @return RetrieveTags
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
     * @return RetrieveTags
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
     * @return RetrieveTags
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
     * @return RetrieveTags
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
     * Set tagType
     *
     * @param string $tagType
     *
     * @return RetrieveTags
     */
    public function setTagType($tagType)
    {
        $this->tagType = $tagType;

        return $this;
    }

    /**
     * Get tagType
     *
     * @return string
     */
    public function getTagType()
    {
        return $this->tagType;
    }

    /**
     * Set animalType
     *
     * @param integer $animalType
     *
     * @return RetrieveTags
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
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return RetrieveTags
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;
        $this->setUbn($location ? $location->getUbn() : null);

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
     * @return bool
     */
    public function isManual()
    {
        return $this->isManual;
    }

    /**
     * @param bool $isManual
     * @return RetrieveTags
     */
    public function setIsManual($isManual)
    {
        $this->isManual = $isManual;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHasForceDeleteAnimalsFailed()
    {
        return $this->hasForceDeleteAnimalsFailed;
    }

    /**
     * @param bool $hasForceDeleteAnimalsFailed
     * @return RetrieveTags
     */
    public function setHasForceDeleteAnimalsFailed($hasForceDeleteAnimalsFailed)
    {
        $this->hasForceDeleteAnimalsFailed = $hasForceDeleteAnimalsFailed;
        return $this;
    }



}
