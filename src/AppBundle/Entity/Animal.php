<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Animal
 *
 * @ORM\Table(name="animal")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"Animal" = "Animal", "Ram" = "Ram", "Ewe" = "Ewe", "Neuter" = "Neuter"})
 * @package AppBundle\Entity\Animal
 * @ExclusionPolicy("all")
 */
abstract class Animal
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
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
     * @Expose
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
     * @Expose
     */
    protected $pedigreeNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    protected $dateOfBirth;

    /**
     * @var string
     *
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    protected $dateOfDeath;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    protected $gender;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_father_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    protected $parentFather;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_mother_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    protected $parentMother;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Neuter", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_neuter_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    protected $parentNeuter;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="surrogateChildren", cascade={"persist"})
     * @ORM\JoinColumn(name="surrogate_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    protected $surrogate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @Expose
     */
    protected $animalType;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     * @Expose
     */
    protected $animalCategory;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    protected $animalHairColour;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareArrival")
     * @ORM\OneToMany(targetEntity="DeclareArrival", mappedBy="animal", cascade={"persist"})
     */
    protected $arrivals;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareDepart")
     * @ORM\OneToMany(targetEntity="DeclareDepart", mappedBy="animal", cascade={"persist"})
     */
    protected $departures;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareImport")
     * @ORM\OneToMany(targetEntity="DeclareImport", mappedBy="animal", cascade={"persist"})
     */
    protected $imports;

    /**
     * @var DeclareBirth
     *
     * @JMS\Type("AppBundle\Entity\DeclareBirth")
     * @ORM\OneToOne(targetEntity="DeclareBirth", mappedBy="animal")
     * @Expose
     */
    protected $birth;

    /**
     * @var Tag
     *
     * @ORM\OneToOne(targetEntity="Tag", inversedBy="animal", cascade={"persist"})
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Tag")
     * @Expose
     */
    protected $assignedTag;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="animals", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Expose
     */
    protected $isAlive;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    protected $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    protected $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    protected $animalOrderNumber;

    /**
     * Animal constructor.
     */
    public function __construct() {
        $this->arrivals = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->departures = new ArrayCollection();
        $this->imports = new ArrayCollection();
        $this->isAlive = true;

        $this->ulnCountryCode = '';
        $this->ulnNumber = '';
        $this->animalOrderNumber = '';
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
     * Get ulnCountryCode
     *
     * @return string
     */
    public function getUlnCountryCode()
    {
        if($this->getAssignedTag() != null) {
            return $this->getAssignedTag()->getUlnCountryCode();
        }

        return null;
    }

    /**
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        if($this->getAssignedTag() != null) {
            return $this->getAssignedTag()->getUlnNumber();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getAnimalOrderNumber()
    {
        if($this->getAssignedTag() != null){
            $this->getAssignedTag()->getAnimalOrderNumber();
        }

        return null;
    }

    /**
     * Set assignedTag
     *
     * @param \AppBundle\Entity\Tag $assignedTag
     *
     * @return Animal
     */
    public function setAssignedTag(\AppBundle\Entity\Tag $assignedTag = null)
    {
        if($assignedTag != null){
            $this->assignedTag = $assignedTag;
            $this->assignedTag->setTagStatus(Constant::ASSIGNED_NAMESPACE);
            $assignedTag->setAnimal($this);
            $this->setUlnNumber($assignedTag->getUlnNumber());
            $this->setUlnCountryCode($assignedTag->getUlnCountryCode());
            $this->setAnimalOrderNumber($assignedTag->getAnimalOrderNumber());
        }

        return $this;
    }

    /**
     * Get assignedTag
     *
     * @return \AppBundle\Entity\Tag
     */
    public function getAssignedTag()
    {
        return $this->assignedTag;
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
     * Add arrival
     *
     * @param \AppBundle\Entity\DeclareArrival $arrival
     *
     * @return Animal
     */
    public function addArrival(\AppBundle\Entity\DeclareArrival $arrival)
    {
        $this->arrivals[] = $arrival;

        return $this;
    }

    /**
     * Remove arrival
     *
     * @param \AppBundle\Entity\DeclareArrival $arrival
     */
    public function removeArrival(\AppBundle\Entity\DeclareArrival $arrival)
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

    /**
     * Add departure
     *
     * @param \AppBundle\Entity\DeclareDepart $depart
     *
     * @return Animal
     */
    public function addDeparture(\AppBundle\Entity\DeclareDepart $depart)
    {
        $this->departures[] = $depart;

        return $this;
    }

    /**
     * Remove depart
     *
     * @param \AppBundle\Entity\DeclareDepart $depart
     */
    public function removeDeparture(\AppBundle\Entity\DeclareDepart $depart)
    {
        $this->departures->removeElement($depart);
    }

    /**
     * Get departures
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDepartures()
    {
        return $this->departures;
    }

    /**
     * Set parentNeuter
     *
     * @param \AppBundle\Entity\Neuter $parentNeuter
     *
     * @return Animal
     */
    public function setParentNeuter(\AppBundle\Entity\Neuter $parentNeuter = null)
    {
        $this->parentNeuter = $parentNeuter;

        return $this;
    }

    /**
     * Get parentNeuter
     *
     * @return \AppBundle\Entity\Neuter
     */
    public function getParentNeuter()
    {
        return $this->parentNeuter;
    }

    /**
     * Add import
     *
     * @param \AppBundle\Entity\DeclareImport $import
     *
     * @return Animal
     */
    public function addImport(\AppBundle\Entity\DeclareImport $import)
    {
        $this->imports[] = $import;

        return $this;
    }

    /**
     * Remove import
     *
     * @param \AppBundle\Entity\DeclareImport $import
     */
    public function removeImport(\AppBundle\Entity\DeclareImport $import)
    {
        $this->imports->removeElement($import);
    }

    /**
     * Get imports
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * @return string
     */
    public function getAnimalHairColour()
    {
        return $this->animalHairColour;
    }

    /**
     * @param string $animalHairColour
     */
    public function setAnimalHairColour($animalHairColour)
    {
        $this->animalHairColour = $animalHairColour;
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
     * Set parentFather
     *
     * @param \AppBundle\Entity\Ram $parentFather
     *
     * @return Animal
     */
    public function setParentFather(\AppBundle\Entity\Ram $parentFather = null)
    {
        $this->parentFather = $parentFather;
        //$parentFather->getChildren()->add($this);

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
        //$parentMother->getChildren()->add($this);

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
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return Animal
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
     * Set isAlive
     *
     * @param boolean $isAlive
     *
     * @return Animal
     */
    public function setIsAlive($isAlive)
    {
        $this->isAlive = $isAlive;

        return $this;
    }

    /**
     * Get isAlive
     *
     * @return boolean
     */
    public function getIsAlive()
    {
        return $this->isAlive;
    }

    /**
     * Set birth
     *
     * @param \AppBundle\Entity\DeclareBirth $birth
     *
     * @return Animal
     */
    public function setBirth(\AppBundle\Entity\DeclareBirth $birth = null)
    {
        $this->birth = $birth;

        return $this;
    }

    /**
     * Get birth
     *
     * @return \AppBundle\Entity\DeclareBirth
     */
    public function getBirth()
    {
        return $this->birth;
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
     * Set animalOrderNumber
     *
     * @param string $animalOrderNumber
     *
     * @return Animal
     */
    public function setAnimalOrderNumber($animalOrderNumber)
    {
        $this->animalOrderNumber = $animalOrderNumber;

        return $this;
    }

    /**
     * Set surrogate
     *
     * @param \AppBundle\Entity\Ewe $surrogate
     *
     * @return Animal
     */
    public function setSurrogate(\AppBundle\Entity\Ewe $surrogate = null)
    {
        $this->surrogate = $surrogate;

        return $this;
    }


    /**
     * Get surrogate
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getSurrogate()
    {
        return $this->surrogate;
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

}
