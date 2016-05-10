<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class DeclareDepart
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareDepartRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class DeclareDepart extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="departures", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    private $animal;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $departDate;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 10)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @Expose
     */
    private $ubnNewOwner;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="departures", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $transportationCode;

    /**
     * @ORM\OneToMany(targetEntity="DeclareDepartResponse", mappedBy="declareDepartRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_depart_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

    /**
     * DeclareDepart constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->setRequestState('open');

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareDepartResponse $response
     *
     * @return DeclareDepart
     */
    public function addResponse(\AppBundle\Entity\DeclareDepartResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareDepartResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareDepartResponse $response)
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
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareDepart
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
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareDepart
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDepartDate()
    {
        return $this->departDate;
    }

    /**
     * @param \DateTime $departDate
     *
     * @return DeclareDepart
     */
    public function setDepartDate($departDate)
    {
        $this->departDate = $departDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getUbnNewOwner()
    {
        return $this->ubnNewOwner;
    }

    /**
     * @param string $ubnNewOwner
     *
     * @return DeclareDepart
     */
    public function setUbnNewOwner($ubnNewOwner)
    {
        $this->ubnNewOwner = $ubnNewOwner;

        return $this;
    }

    /**
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param \AppBundle\Entity\Location $location
     *
     * @return \AppBundle\Entity\DeclareDepart
     */
    public function setLocation($location)
    {
        $this->location = $location;
        $this->setUbn($this->location->getUbn());

        return $this;
    }

    /**
     * @return string
     */
    public function getTransportationCode()
    {
        return $this->transportationCode;
    }

    /**
     * @param string $transportationCode
     *
     * @return \AppBundle\Entity\DeclareDepart
     */
    public function setTransportationCode($transportationCode)
    {
        $this->transportationCode = $transportationCode;

        return $this;
    }

}
