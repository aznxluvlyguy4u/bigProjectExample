<?php

namespace AppBundle\Entity;

use AppBundle\Component\MessageBuilderBase;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareTagsTransfer
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareTagsTransferRepository")
 * @package AppBundle\Entity
 */
class DeclareTagsTransfer extends DeclareBase
{
    use EntityClassInfo;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="declareTagsTransferRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_tag_transfer_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $tags;

    /**
     * @ORM\ManyToMany(targetEntity="TagTransferItemRequest", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="transfer_requests",
     *      joinColumns={@ORM\JoinColumn(name="declare_tags_transfer_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_transfer_item_request_id", referencedColumnName="id", unique=true)}
     *      )
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     */
    private $tagTransferRequests;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     */
    private $relationNumberAcceptant;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     */
    private $ubnNewOwner;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="tagTransfers", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareTagsTransferResponse", mappedBy="declareTagsTransferRequestMessage", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="declare_tag_transfer_request_message_id", referencedColumnName="id")
     * @ORM\OrderBy({"logDate" = "ASC"})
     * @JMS\Type("array")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     */
    private $responses;

    /**
     * DeclareTagsTransfer constructor.
     */
    function __construct() {
        parent::__construct();

        $this->setRequestState(RequestStateType::OPEN);

        //Create responses array
        $this->responses = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->setLogDate(new \DateTime());
    }


    /**
     * Add tag
     *
     * @param \AppBundle\Entity\Tag $tag
     *
     * @return DeclareTagsTransfer
     */
    public function addTag(\AppBundle\Entity\Tag $tag)
    {
        $this->tags[] = $tag;

        if($this->requestId == null) {
            $this->setRequestId(MessageBuilderBase::getNewRequestId());
        }

        $tagTransferItemRequest = new TagTransferItemRequest();
        $tagTransferItemRequest->setRequestId($this->getRequestId());
        $tagTransferItemRequest->setMessageId($this->getMessageId());
        $tagTransferItemRequest->setRequestState(RequestStateType::OPEN);
        $tagTransferItemRequest->setAnimalType(AnimalType::sheep);
        $tagTransferItemRequest->setRelationNumberAcceptant($this->relationNumberAcceptant);
        $tagTransferItemRequest->setUbnNewOwner($this->ubnNewOwner);
        $tagTransferItemRequest->setUlnCountryCode($tag->getUlnCountryCode());
        $tagTransferItemRequest->setUlnNumber($tag->getUlnNumber());
        $tagTransferItemRequest->setTag($tag);
        $tag->setDeclareTagsTransferRequestMessage($this);

        $this->addTagTransferRequest($tagTransferItemRequest);

        return $this;
    }

    /**
     * Remove tag
     *
     * @param \AppBundle\Entity\Tag $tag
     */
    public function removeTag(\AppBundle\Entity\Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareImportResponse $response
     *
     * @return DeclareTagsTransfer
     */
    public function addResponse(\AppBundle\Entity\DeclareImportResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareImportResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareImportResponse $response)
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
     * Set relationNumberAcceptant
     *
     * @param string $relationNumberAcceptant
     *
     * @return DeclareTagsTransfer
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
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return DeclareTagsTransfer
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
     * Set ubnNewOwner
     *
     * @param string $ubnNewOwner
     *
     * @return DeclareTagsTransfer
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
     * Add tagTransferRequest
     *
     * @param \AppBundle\Entity\TagTransferItemRequest $tagTransferRequest
     *
     * @return DeclareTagsTransfer
     */
    public function addTagTransferRequest(\AppBundle\Entity\TagTransferItemRequest $tagTransferRequest)
    {
        $this->tagTransferRequests[] = $tagTransferRequest;

        return $this;
    }

    /**
     * Remove tagTransferRequest
     *
     * @param \AppBundle\Entity\TagTransferItemRequest $tagTransferRequest
     */
    public function removeTagTransferRequest(\AppBundle\Entity\TagTransferItemRequest $tagTransferRequest)
    {
        $this->tagTransferRequests->removeElement($tagTransferRequest);
    }

    /**
     * Get tagTransferRequests
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTagTransferRequests()
    {
        return $this->tagTransferRequests;
    }
}
