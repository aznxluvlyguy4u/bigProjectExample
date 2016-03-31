<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Animal
 *
 * @ORM\Table(name="animal")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @package AppBundle\Entity\Animal
 */
abstract class Animal
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * Country code as defined by ISO 3166-1:
     * {https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2}
     *
     * Example: NL(Netherlands), IE(Ireland), DK(Denmark), SE(Sweden)
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Regex("/([A-Z]{2})\b/")
     * @Assert\Length(max = 2)
     * @JMS\Type("string")
     */
    protected $pedigreeCountryCode;

    /**
     * @var string
     *
     * Example: 17263-12345
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 11)
     * @JMS\Type("string")
     */
    protected $pedigreeNumber;

    /**
     * @var string
     *
     * Country code as defined by ISO 3166-1:
     * {https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2}
     *
     * Example: NL(Netherlands), IE(Ireland), DK(Denmark), SE(Sweden)
     *
     * @ORM\Column(type="string")
     * @Assert\Regex("/([A-Z]{2})\b/")
     * @Assert\Length(max = 2)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    protected $ulnCountryCode;

    /**
     * @var string
     *
     * Example: 000000012345
     *
     * @ORM\Column(type="string")
     * @Assert\Regex("/([0-9]{12})\b/")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    protected $ulnNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    protected $dateOfBirth;

    /**
     * @var string
     *
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    protected $dateOfDeath;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    protected $gender;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy="children")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    protected $parentFather;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="children")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    protected $parentMother;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    protected $animalType;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    protected $animalCategory;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Arrival", mappedBy="animal", cascade={"persist"})
     */
    protected $arrivals;

    /**
     * @var array
     * @JMS\Type("array")
     */
    public $children;

    /**
     * Animal constructor.
     */
    public function __construct() {
        $this->arrivals = new ArrayCollection();
        $this->children = new ArrayCollection();
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
     * Set pedigreeCountryCode
     *
     * @param string $pedigreeCountryCode
     *
     * @return Animal
     */
    public function setPedigreeCountryCode($pedigreeCountryCode)
    {
        $this->pedigreeCountryCode = $pedigreeCountryCode;

        return $this;
    }

    /**
     * Get pedigreeCountryCode
     *
     * @return string
     */
    public function getPedigreeCountryCode()
    {
        return $this->pedigreeCountryCode;
    }

    /**
     * Set pedigreeNumber
     *
     * @param string $pedigreeNumber
     *
     * @return Animal
     */
    public function setPedigreeNumber($pedigreeNumber)
    {
        $this->pedigreeNumber = $pedigreeNumber;

        return $this;
    }

    /**
     * Get pedigreeNumber
     *
     * @return string
     */
    public function getPedigreeNumber()
    {
        return $this->pedigreeNumber;
    }

    /**
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return Animal
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Get ulnCountryCode
     *
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return Animal
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Animal
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     *
     * @return Animal
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
     * Set dateOfDeath
     *
     * @param \DateTime $dateOfDeath
     *
     * @return Animal
     */
    public function setDateOfDeath($dateOfDeath)
    {
        $this->dateOfDeath = $dateOfDeath;

        return $this;
    }

    /**
     * Get dateOfDeath
     *
     * @return \DateTime
     */
    public function getDateOfDeath()
    {
        return $this->dateOfDeath;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return Animal
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set animalType
     *
     * @param integer $animalType
     *
     * @return Animal
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

    /**
     * Set animalCategory
     *
     * @param integer $animalCategory
     *
     * @return Animal
     */
    public function setAnimalCategory($animalCategory)
    {
        $this->animalCategory = $animalCategory;

        return $this;
    }

    /**
     * Get animalCategory
     *
     * @return integer
     */
    public function getAnimalCategory()
    {
        return $this->animalCategory;
    }

    /**
     * Set parentFather
     *
     * @param \AppBundle\Entity\Ram $parentFather
     *
     * @return Animal
     */
    public function setParentFather(\AppBundle\Entity\Ram $parentFather = null)
    {
        $this->parentFather = $parentFather;

        return $this;
    }

    /**
     * Get parentFather
     *
     * @return \AppBundle\Entity\Ram
     */
    public function getParentFather()
    {
        return $this->parentFather;
    }

    /**
     * Set parentMother
     *
     * @param \AppBundle\Entity\Ewe $parentMother
     *
     * @return Animal
     */
    public function setParentMother(\AppBundle\Entity\Ewe $parentMother = null)
    {
        $this->parentMother = $parentMother;

        return $this;
    }

    /**
     * Get parentMother
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getParentMother()
    {
        return $this->parentMother;
    }

    /**
     * Add arrival
     *
     * @param \AppBundle\Entity\Arrival $arrival
     *
     * @return Animal
     */
    public function addArrival(\AppBundle\Entity\Arrival $arrival)
    {
        $this->arrivals[] = $arrival;

        return $this;
    }

    /**
     * Remove arrival
     *
     * @param \AppBundle\Entity\Arrival $arrival
     */
    public function removeArrival(\AppBundle\Entity\Arrival $arrival)
    {
        $this->arrivals->removeElement($arrival);
    }

    /**
     * Get arrivals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArrivals()
    {
        return $this->arrivals;
    }
}
