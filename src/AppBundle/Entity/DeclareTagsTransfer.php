<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareTagsTransfer
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareTagsTransferRepository")
 * @package AppBundle\Entity
 */
class DeclareTagsTransfer extends DeclareBase
{

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="declareTagTransferRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_tag_transfer_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $tags;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $relationNumberAcceptant;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareTagsTransferResponse", mappedBy="declareTagsTransferRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_tag_transfer_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

    /**
     * DeclareTagsTransfer constructor.
     */
    function __construct() {
        parent::__construct();

        $this->setRequestState('open');

        //Create responses array
        $this->responses = new ArrayCollection();
        $this->tags = new ArrayCollection();
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
}
