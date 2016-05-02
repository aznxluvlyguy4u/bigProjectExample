<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareBirth
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBirthRepository")
 * @package AppBundle\Entity
 */
class DeclareBirth extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="births", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    private $animal;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="births", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     * @Expose
     */
    private $ubnPreviousOwner;

    /**
     * @ORM\OneToMany(targetEntity="DeclareBirthResponse", mappedBy="declareBirthRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_birth_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     * @Expose
     */
    private $responses;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareBirth
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
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareBirth
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
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return DeclareBirth
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
     * Set ubnPreviousOwner
     *
     * @param string $ubnPreviousOwner
     *
     * @return DeclareBirth
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
}
