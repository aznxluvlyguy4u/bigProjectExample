<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveEartags
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveEartagsRepository")
 * @package AppBundle\Entity
 */
class RetrieveEartags extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="imports", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @var string
     */
    private $tagType;

    /**
     * @var integer
     */
    private $animalType;

    /**
     * @ORM\OneToMany(targetEntity="RetrieveEartagsResponse", mappedBy="retrieveEartagsRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="retrieve_eartags_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return RetrieveEartags
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;

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
     * @param \AppBundle\Entity\RetrieveEartagsResponse $response
     *
     * @return RetrieveEartags
     */
    public function addResponse(\AppBundle\Entity\RetrieveEartagsResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\RetrieveEartagsResponse $response
     */
    public function removeResponse(\AppBundle\Entity\RetrieveEartagsResponse $response)
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
