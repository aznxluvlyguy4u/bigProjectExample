<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\RequestStateType;
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
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="births", cascade={"persist", "remove"})
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
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @Expose
     */
    private $isAborted;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @Expose
     */
    private $isPseudoPregnancy;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @Expose
     */
    private $hasLambar;

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
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     * @Expose
     */
    private $litterSize;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     * @JMS\Type("float")
     * @Expose
     */
    private $birthWeight;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     * @JMS\Type("float")
     * @Expose
     */
    private $birthTailLength;

    /**
     * @ORM\OneToMany(targetEntity="DeclareBirthResponse", mappedBy="declareBirthRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_birth_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     * @Expose
     */
    private $responses;

    /**
     * @ORM\OneToOne(targetEntity="RevokeDeclaration", inversedBy="birth", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
     * @Expose
     */
    private $revoke;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->setRequestState(RequestStateType::OPEN);

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
     * Set isAborted
     *
     * @param boolean $isAborted
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
     * @return boolean
     */
    public function getIsAborted()
    {
        return $this->isAborted;
    }

    /**
     * Set isPseudoPregnancy
     *
     * @param boolean $isPseudoPregnancy
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
     * @return boolean
     */
    public function getIsPseudoPregnancy()
    {
        return $this->isPseudoPregnancy;
    }

    /**
     * Set hasLambar
     *
     * @param boolean $hasLambar
     *
     * @return DeclareBirth
     */
    public function setHasLambar($hasLambar)
    {
        $this->hasLambar = $hasLambar;

        return $this;
    }

    /**
     * Get hasLambar
     *
     * @return boolean
     */
    public function getHasLambar()
    {
        return $this->hasLambar;
    }

    /**
     * @return RevokeDeclaration
     */
    public function getRevoke()
    {
        return $this->revoke;
    }

    /**
     * @param RevokeDeclaration $revoke
     */
    public function setRevoke($revoke = null)
    {
        $this->revoke = $revoke;
    }
}
