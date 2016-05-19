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
class RetrieveTagsResponse {

    /**
     * @var RetrieveTags
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="RetrieveTags", cascade={"persist"}, inversedBy="responses")
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
}
