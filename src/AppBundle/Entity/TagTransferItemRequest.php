<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\AnimalType;
use Symfony\Component\Validator\Constraints\DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class TagTransferItemRequest
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TagTransferItemRequestRepository")
 * @package AppBundle\Entity
 */
class TagTransferItemRequest {

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $ulnNumber;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     */
    private $animalType;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $ubnNewOwner;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $relationNumberAcceptant;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $requestState;

    /**
     * @var Tag
     * @JMS\Type("AppBundle\Entity\Tag")
     */
    private $tag;

    /**
     * @ORM\OneToMany(targetEntity="TagTransferItemResponse", mappedBy="tagTransferItemRequest", cascade={"persist"})
     * @ORM\JoinColumn(name="tag_transfer_item_request_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    private $actionBy;

    /**
     * TagTransferItemRequest constructor.
     */
    public function __construct() {
        $this->logDate = new \DateTime();
        $this->animalType = AnimalType::sheep;
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
     * Set requestId
     *
     * @param string $requestId
     *
     * @return TagTransferItemRequest
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;

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
     * @return TagTransferItemRequest
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
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return TagTransferItemRequest
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Get ulnCountryCode
     *
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return TagTransferItemRequest
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * Set ubnNewOwner
     *
     * @param string $ubnNewOwner
     *
     * @return TagTransferItemRequest
     */
    public function setUbnNewOwner($ubnNewOwner)
    {
        $this->ubnNewOwner = $ubnNewOwner;

        return $this;
    }

    /**
     * Get ubnNewOwner
     *
     * @return string
     */
    public function getUbnNewOwner()
    {
        return $this->ubnNewOwner;
    }

    /**
     * Set relationNumberAcceptant
     *
     * @param string $relationNumberAcceptant
     *
     * @return TagTransferItemRequest
     */
    public function setRelationNumberAcceptant($relationNumberAcceptant)
    {
        $this->relationNumberAcceptant = $relationNumberAcceptant;

        return $this;
    }

    /**
     * Get relationNumberAcceptant
     *
     * @return string
     */
    public function getRelationNumberAcceptant()
    {
        return $this->relationNumberAcceptant;
    }

    /**
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return TagTransferItemRequest
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
     * Set requestState
     *
     * @param string $requestState
     *
     * @return TagTransferItemRequest
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
     * Set animalType
     *
     * @param integer $animalType
     *
     * @return TagTransferItemRequest
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
     * Add response
     *
     * @param \AppBundle\Entity\TagTransferItemResponse $response
     *
     * @return TagTransferItemRequest
     */
    public function addResponse(\AppBundle\Entity\TagTransferItemResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\TagTransferItemResponse $response
     */
    public function removeResponse(\AppBundle\Entity\TagTransferItemResponse $response)
    {
        $this->responses->removeElement($response);
    }

    /**
     * Get responses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Set tag
     *
     * @param \AppBundle\Entity\Tag $tag
     *
     * @return TagTransferItemRequest
     */
    public function setTag(\AppBundle\Entity\Tag $tag = null)
    {
        $this->tag = $tag;
        $this->setUlnCountryCode($tag->getUlnCountryCode());
        $this->setUlnNumber($tag->getUlnNumber());

        return $this;
    }

    /**
     * Get tag
     *
     * @return \AppBundle\Entity\Tag
     */
    public function getTag()
    {
        return $this->tag;
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
}
