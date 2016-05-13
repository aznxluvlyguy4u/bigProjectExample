<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareEartagsTransfer
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareEartagsTransferRepository")
 * @package AppBundle\Entity
 */
class DeclareEartagsTransfer extends DeclareBase
{

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="declareEartagTransferRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_eartag_transfer_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $tags;

    /**
     * @var string
     */
    private $relationNumberAcceptant;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareEartagsTransferResponse", mappedBy="declareEartagsTransferRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_eartag_transfer_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

    /**
     * DeclareEartagsTransfer constructor.
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
     * @return DeclareEartagsTransfer
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
     * @return DeclareEartagsTransfer
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
}
