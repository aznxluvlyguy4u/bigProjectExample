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
 * Class DeclareArrival
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareArrivalRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class DeclareArrival extends DeclareBase {

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="arrivals", cascade={"persist"})
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
    private $arrivalDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     * @Expose
     */
    private $ubnPreviousOwner;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="arrivals", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Expose
     */
    private $importAnimal;

    /**
     * @ORM\OneToMany(targetEntity="DeclareArrivalResponse", mappedBy="declareArrivalRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_arrival_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     * @Expose
     */
    private $responses;

    /**
     * DeclareArrival constructor.
     */
    public function __construct() {
        parent::__construct();

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Set arrivalDate
     *
     * @param \DateTime $arrivalDate
     *
     * @return DeclareArrival
     */
    public function setArrivalDate($arrivalDate)
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    /**
     * Get arrivalDate
     *
     * @return \DateTime
     */
    public function getArrivalDate()
    {
        return $this->arrivalDate;
    }

    /**
     * Set ubnPreviousOwner
     *
     * @param string $ubnPreviousOwner
     *
     * @return DeclareArrival
     */
    public function setUbnPreviousOwner($ubnPreviousOwner)
    {
        $this->ubnPreviousOwner = $ubnPreviousOwner;

        return $this;
    }

    /**
     * Get ubnPreviousOwner
     *
     * @return string
     */
    public function getUbnPreviousOwner()
    {
        return $this->ubnPreviousOwner;
    }


    /**
     * Set importAnimal
     *
     * @param boolean $importAnimal
     *
     * @return DeclareArrival
     */
    public function setImportAnimal($importAnimal)
    {
        $this->importAnimal = $importAnimal;

        return $this;
    }

    /**
     * Get importAnimal
     *
     * @return boolean
     */
    public function getImportAnimal()
    {
        return $this->importAnimal;
    }

    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return DeclareArrival
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;
        $this->setUbn($this->location->getUbn());

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
     * @param \AppBundle\Entity\DeclareArrivalResponse $response
     *
     * @return DeclareArrival
     */
    public function addResponse(\AppBundle\Entity\DeclareArrivalResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareArrivalResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareArrivalResponse $response)
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareArrival
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareArrival
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
}
