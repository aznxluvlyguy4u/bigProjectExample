<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimals
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalsRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimals extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\OneToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;


    /**
     * @ORM\OneToMany(targetEntity="RetrieveAnimalsResponse", mappedBy="retrieveAnimalsRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="retrieve_animals_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

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
     * @param \AppBundle\Entity\RetrieveAnimalsResponse $response
     *
     * @return RetrieveAnimals
     */
    public function addResponse(\AppBundle\Entity\RetrieveAnimalsResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\RetrieveAnimalsResponse $response
     */
    public function removeResponse(\AppBundle\Entity\RetrieveAnimalsResponse $response)
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
