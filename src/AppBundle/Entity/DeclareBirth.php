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
 * Class DeclareBirth
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBirthRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class DeclareBirth extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\OneToOne(targetEntity="Animal", inversedBy="birth", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
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
     * @Assert\Length(max = 1)
     * @JMS\Type("string")
     * @Expose
     */
    private $isAborted;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 1)
     * @JMS\Type("string")
     * @Expose
     */
    private $isPseudoPregnancy;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 1)
     * @JMS\Type("string")
     * @Expose
     */
    private $isLambar;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $dateOfBirth;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 100)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @Expose
     */
    private $birthType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Length(max = 2)
     * @JMS\Type("integer")
     * @Expose
     */
    private $litterSize;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Length(max = 3)
     * @JMS\Type("integer")
     * @Expose
     */
    private $birthWeight;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Length(max = 4)
     * @JMS\Type("integer")
     * @Expose
     */
    private $birthTailLength;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 50)
     * @JMS\Type("string")
     * @Expose
     */
    private $transportationCode;

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
        $this->setRequestState('open');

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareBirthResponse $response
     *
     * @return DeclareBirth
     */
    public function addResponse(\AppBundle\Entity\DeclareBirthResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareBirthResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareBirthResponse $response)
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
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     *
     * @return DeclareBirth
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
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
     * @return string
     */
    public function getBirthType()
    {
        return $this->birthType;
    }

    /**
     * @param string $birthType
     */
    public function setBirthType($birthType)
    {
        $this->birthType = $birthType;
    }

    /**
     * @return integer
     */
    public function getLitterSize()
    {
        return $this->litterSize;
    }

    /**
     * @param integer $litterSize
     */
    public function setLitterSize($litterSize)
    {
        $this->litterSize = $litterSize;
    }

    /**
     * @return integer
     */
    public function getBirthWeight()
    {
        return $this->birthWeight;
    }

    /**
     * @param integer $birthWeight
     */
    public function setBirthWeight($birthWeight)
    {
        $this->birthWeight = $birthWeight;
    }

    /**
     * @return integer
     */
    public function getBirthTailLength()
    {
        return $this->birthTailLength;
    }

    /**
     * @param integer $birthTailLength
     */
    public function setBirthTailLength($birthTailLength)
    {
        $this->birthTailLength = $birthTailLength;
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
     */
    public function setTransportationCode($transportationCode)
    {
        $this->transportationCode = $transportationCode;
    }


    /**
     * Set isAborted
     *
     * @param string $isAborted
     *
     * @return DeclareBirth
     */
    public function setIsAborted($isAborted)
    {
        $this->isAborted = $isAborted;

        return $this;
    }

    /**
     * Get isAborted
     *
     * @return string
     */
    public function getIsAborted()
    {
        return $this->isAborted;
    }

    /**
     * Set isPseudoPregnancy
     *
     * @param string $isPseudoPregnancy
     *
     * @return DeclareBirth
     */
    public function setIsPseudoPregnancy($isPseudoPregnancy)
    {
        $this->isPseudoPregnancy = $isPseudoPregnancy;

        return $this;
    }

    /**
     * Get isPseudoPregnancy
     *
     * @return string
     */
    public function getIsPseudoPregnancy()
    {
        return $this->isPseudoPregnancy;
    }

    /**
     * Set isLambar
     *
     * @param string $isLambar
     *
     * @return DeclareBirth
     */
    public function setIsLambar($isLambar)
    {
        $this->isLambar = $isLambar;

        return $this;
    }

    /**
     * Get isLambar
     *
     * @return string
     */
    public function getIsLambar()
    {
        return $this->isLambar;
    }
}
