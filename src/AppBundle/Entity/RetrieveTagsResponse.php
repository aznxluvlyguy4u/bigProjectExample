<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class RetrieveTagsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveTagsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveTagsResponse
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var string;
     *
     * @ORM\Column(type="string", nullable=true)
     *
     */
    private $errorCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $errorMessage;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 1)
     */
    private $errorKindIndicator;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 1)
     */
    private $successIndicator;

    /**
     * @var RetrieveAnimals
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="RetrieveTags")
     * @ORM\JoinColumn(name="retrieve_tags_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\RetrieveTags")
     */
    private $retrieveTagsRequestMessage;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Tag")
     * @ORM\JoinTable(name="retrieve_tags_response_tags_retrieved",
     *      joinColumns={@ORM\JoinColumn(name="retrieve_tags_response_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id", unique=true)}
     * )
     * @JMS\Type("AppBundle\Entity\Tag")
     */
    private $tagsRetrieved;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    private $actionBy;

    /**
     * RetrieveTagsResponse constructor.
     */
    public function __construct() {
      $this->tagsRetrieved = new ArrayCollection();
    }

  /**
     * Set retrieveTagsRequestMessage
     *
     * @param \AppBundle\Entity\RetrieveTags $retrieveTagsRequestMessage
     *
     * @return RetrieveTagsResponse
     */
    public function setRetrieveTagsRequestMessage(\AppBundle\Entity\RetrieveTags $retrieveTagsRequestMessage = null)
    {
        $this->retrieveTagsRequestMessage = $retrieveTagsRequestMessage;

        return $this;
    }

    /**
     * Get retrieveTagsRequestMessage
     *
     * @return \AppBundle\Entity\RetrieveTags
     */
    public function getRetrieveTagsRequestMessage()
    {
        return $this->retrieveTagsRequestMessage;
    }

    /**
     * Add tagsRetrieved
     *
     * @param \AppBundle\Entity\Tag $tagsRetrieved
     *
     * @return RetrieveTagsResponse
     */
    public function addTagsRetrieved(\AppBundle\Entity\Tag $tagsRetrieved)
    {
        $this->tagsRetrieved[] = $tagsRetrieved;

        return $this;
    }

    /**
     * Remove tagsRetrieved
     *
     * @param \AppBundle\Entity\Tag $tagsRetrieved
     */
    public function removeTagsRetrieved(\AppBundle\Entity\Tag $tagsRetrieved)
    {
        $this->tagsRetrieved->removeElement($tagsRetrieved);
    }

    /**
     * Get tagsRetrieved
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTagsRetrieved()
    {
        return $this->tagsRetrieved;
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
     * @return RetrieveTagsResponse
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
     * @return RetrieveTagsResponse
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
     * @return RetrieveTagsResponse
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
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return RetrieveTagsResponse
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set errorMessage
     *
     * @param string $errorMessage
     *
     * @return RetrieveTagsResponse
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * Get errorMessage
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Set errorKindIndicator
     *
     * @param string $errorKindIndicator
     *
     * @return RetrieveTagsResponse
     */
    public function setErrorKindIndicator($errorKindIndicator)
    {
        $this->errorKindIndicator = $errorKindIndicator;

        return $this;
    }

    /**
     * Get errorKindIndicator
     *
     * @return string
     */
    public function getErrorKindIndicator()
    {
        return $this->errorKindIndicator;
    }

    /**
     * Set successIndicator
     *
     * @param string $successIndicator
     *
     * @return RetrieveTagsResponse
     */
    public function setSuccessIndicator($successIndicator)
    {
        $this->successIndicator = $successIndicator;

        return $this;
    }

    /**
     * Get successIndicator
     *
     * @return string
     */
    public function getSuccessIndicator()
    {
        return $this->successIndicator;
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
