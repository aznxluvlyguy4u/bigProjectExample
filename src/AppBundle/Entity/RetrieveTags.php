<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveTags
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveTagsRepository")
 * @package AppBundle\Entity
 */
class RetrieveTags extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="imports", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $tagType;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\Length(max = 1)
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $animalType;

    /**
     * @ORM\OneToMany(targetEntity="RetrieveTagsResponse", mappedBy="retrieveTagsRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="retrieve_tags_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

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
     * Add response
     *
     * @param \AppBundle\Entity\RetrieveTagsResponse $response
     *
     * @return RetrieveTags
     */
    public function addResponse(\AppBundle\Entity\RetrieveTagsResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\RetrieveTagsResponse $response
     */
    public function removeResponse(\AppBundle\Entity\RetrieveTagsResponse $response)
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
}
