<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;

/**
 * Class Animal
 *
 * @ORM\Table(name="animal",indexes={
 *     @ORM\Index(name="uln_idx", columns={"name", "uln_country_code", "uln_number"}),
 *     @ORM\Index(name="parents_idx", columns={"parent_mother_id", "parent_father_id"}),
 *     @ORM\Index(name="animal_location_idx", columns={"location_id"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"Animal" = "Animal", "Ram" = "Ram", "Ewe" = "Ewe", "Neuter" = "Neuter"})
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                        "Ram" : "AppBundle\Entity\Ram",
 *                        "Ewe" : "AppBundle\Entity\Ewe",
 *                     "Neuter" : "AppBundle\Entity\Neuter"},
 *     groups = {"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS","BASIC"})
 * 
 * @package AppBundle\Entity\Animal
 */
abstract class Animal
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS","BASIC","MIXBLUP"})
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
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS","BASIC","MIXBLUP"})
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
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS","BASIC","MIXBLUP"})
     */
    protected $pedigreeNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS"})
     */
    protected $ubnOfBirth;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="location_of_birth_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $locationOfBirth;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS","BASIC","MIXBLUP"})
     */
    protected $dateOfBirth;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS"})
     */
    protected $dateOfDeath;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS","BASIC","MIXBLUP"})
     */
    protected $gender;

    /**
     * @var Ram
     *
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_father_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @JMS\Groups({"PARENTS"})
     * @JMS\MaxDepth(depth=1)
     */
    protected $parentFather;

    /**
     * @var Ewe
     *
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_mother_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @JMS\Groups({"PARENTS"})
     * @JMS\MaxDepth(depth=1)
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
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Animal")
     * @ORM\JoinTable(name="animal_parents",
     *      joinColumns={@ORM\JoinColumn(name="animal_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="parents_id", referencedColumnName="id", unique=false)}
     * )
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    protected $parents;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="surrogateChildren", cascade={"persist"})
     * @ORM\JoinColumn(name="surrogate_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @JMS\Groups({"PARENTS"})
     * @JMS\MaxDepth(depth=1)
     */
    protected $surrogate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @JMS\Groups({"DECLARE"})
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $animalType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"DECLARE"})
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $transferState;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     * @JMS\Groups({"DECLARE"})
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $animalCategory;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"DECLARE"})
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $animalHairColour;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareArrival")
     * @ORM\OneToMany(targetEntity="DeclareArrival", mappedBy="animal")
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
     * @ORM\OneToMany(targetEntity="DeclareImport", mappedBy="animal")
     */
    protected $imports;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareExport")
     * @ORM\OneToMany(targetEntity="DeclareExport", mappedBy="animal", cascade={"persist"})
     */
    protected $exports;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareBirth")
     * @ORM\OneToMany(targetEntity="DeclareBirth", mappedBy="animal")
     */
    protected $births;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareLoss")
     * @ORM\OneToMany(targetEntity="DeclareLoss", mappedBy="animal", cascade={"persist"})
     */
    protected $deaths;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareAnimalFlag")
     * @ORM\OneToMany(targetEntity="DeclareAnimalFlag", mappedBy="animal", cascade={"persist"})
     */
    protected $flags;

    /**
     * @var array
     * @JMS\Type("AppBundle\Entity\DeclareTagReplace")
     * @ORM\OneToMany(targetEntity="DeclareTagReplace", mappedBy="animal", cascade={"persist"})
     */
    protected $tagReplacements;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("AppBundle\Entity\DeclareWeight")
     * @ORM\OneToMany(targetEntity="DeclareWeight", mappedBy="animal")
     */
    protected $declareWeights;

    /**
     * @var Tag
     *
     * @ORM\OneToOne(targetEntity="Tag", inversedBy="animal", cascade={"persist"})
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\Tag")
     */
    protected $assignedTag;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="animals", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $location;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS","BASIC","MIXBLUP"})
     */
    protected $isAlive;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS","BASIC","MIXBLUP"})
     */
    protected $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @Assert\Regex("/([A-Z]{2})\b/")
     * @Assert\Length(max = 2)
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS","BASIC","MIXBLUP"})
     */
    protected $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS"})
     */
    protected $animalOrderNumber;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS"})
     */
    protected $isImportAnimal;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS"})
     */
    protected $isExportAnimal;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS"})
     */
    protected $isDepartedAnimal;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"DECLARE","ANIMAL_DETAILS"})
     */
    protected $animalCountryOrigin;

    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="Tag")
     * @ORM\JoinTable(name="ulns_history",
     *      joinColumns={@ORM\JoinColumn(name="animal_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id", unique=true)}
     * )
     *
     */
    protected $ulnHistory;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AnimalResidence", mappedBy="animal", cascade={"persist"})
     * @ORM\OrderBy({"startDate" = "ASC"})
     * @JMS\Type("AppBundle\Entity\AnimalResidence")
     */
    protected $animalResidenceHistory;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BodyFat", mappedBy="animal", cascade={"persist"})
     * @ORM\OrderBy({"measurementDate" = "ASC"})
     * @JMS\Type("AppBundle\Entity\BodyFat")
     */
    protected $bodyFatMeasurements;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="MuscleThickness", mappedBy="animal", cascade={"persist"})
     * @ORM\OrderBy({"measurementDate" = "ASC"})
     * @JMS\Type("AppBundle\Entity\MuscleThickness")
     */
    protected $muscleThicknessMeasurements;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="TailLength", mappedBy="animal", cascade={"persist"})
     * @ORM\OrderBy({"measurementDate" = "ASC"})
     * @JMS\Type("AppBundle\Entity\TailLength")
     */
    protected $tailLengthMeasurements;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Weight", mappedBy="animal", cascade={"persist"})
     * @ORM\OrderBy({"measurementDate" = "ASC"})
     * @JMS\Type("AppBundle\Entity\Weight")
     */
    protected $weightMeasurements;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Exterior", mappedBy="animal", cascade={"persist"})
     * @ORM\OrderBy({"measurementDate" = "ASC"})
     * @JMS\Type("AppBundle\Entity\Exterior")
     */
    protected $exteriorMeasurements;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS"})
     */
    protected $breed;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS"})
     */
    protected $breedType;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS"})
     */
    protected $breedCode;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     */
    protected $hasCompleteBreedCode;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    protected $heterosis;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    protected $recombination;


    /**
     * @var boolean
     * @JMS\Type("boolean")
     * @ORM\Column(type="boolean", options={"default":false}, nullable=false)
     */
    protected $updatedGeneDiversity;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="breeder_id", referencedColumnName="id")
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $breeder;

    /**
     * @var BreedCodes
     * @ORM\OneToOne(targetEntity="BreedCodes", inversedBy="animal", cascade={"persist"})
     * @ORM\JoinColumn(name="breed_codes_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\BreedCodes")
     */
    protected $breedCodes;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $predicate;

    /**
     * @var integer
     * @JMS\Type("integer")
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $predicateScore;
    
    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS"})
     */
    protected $scrapieGenotype;

    /**
     * The current blindnessFactor
     *
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $blindnessFactor;


    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true, options={"default":null})
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $myoMax;

    /**
     * @var Litter
     * @JMS\Type("AppBundle\Entity\Litter")
     * @ORM\ManyToOne(targetEntity="Litter", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="litter_id", referencedColumnName="id")
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $litter;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="GenderHistoryItem", mappedBy="animal", cascade={"persist"})
     * @ORM\JoinColumn(name="gender_history_id", referencedColumnName="id")
     */
    protected $genderHistory;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $note;

    /**
     * @var PedigreeRegister
     * @ORM\ManyToOne(targetEntity="PedigreeRegister", cascade={"persist"})
     * @ORM\JoinColumn(name="pedigree_register_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\PedigreeRegister")
     * @JMS\Groups({"DECLARE","USER_MEASUREMENT","ANIMAL_DETAILS"})
     */
    protected $pedigreeRegister;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $birthProgress;

    /**
     * @var boolean
     * @JMS\Type("boolean")
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $lambar;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BreedValuesSet", mappedBy="animal", cascade={"persist"})
     * @ORM\OrderBy({"generationDate" = "ASC"})
     * @JMS\Type("AppBundle\Entity\BreedValuesSet")
     */
    protected $breedValuesSets;

    /**
     * @var ResultTableBreedGrades
     * @ORM\OneToOne(targetEntity="ResultTableBreedGrades", mappedBy="animal", cascade={"persist", "remove"})
     * @JMS\Type("AppBundle\Entity\ResultTableBreedGrades")
     */
    protected $latestBreedGrades;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $nickname;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $collarColor;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    protected $collarNumber;

    /**
     * Animal constructor.
     */
    public function __construct() {
        $this->arrivals = new ArrayCollection();
//        $this->children = new ArrayCollection();
        $this->departures = new ArrayCollection();
        $this->imports = new ArrayCollection();
        $this->exports = new ArrayCollection();
        $this->births = new ArrayCollection();
        $this->deaths = new ArrayCollection();
        $this->animalResidenceHistory = new ArrayCollection();
        $this->bodyFatMeasurements = new ArrayCollection();
        $this->muscleThicknessMeasurements = new ArrayCollection();
        $this->tailLengthMeasurements = new ArrayCollection();
        $this->weightMeasurements = new ArrayCollection();
        $this->declareWeights = new ArrayCollection();
        $this->flags = new ArrayCollection();
        $this->ulnHistory = new ArrayCollection();
        $this->genderHistory = new ArrayCollection();
        $this->tagReplacements = new ArrayCollection();
        $this->parents = new ArrayCollection();
        $this->breedValuesSets = new ArrayCollection();
        $this->isAlive = true;
        $this->ulnCountryCode = '';
        $this->ulnNumber = '';
        $this->animalOrderNumber = '';
        $this->isImportAnimal = false;
        $this->isExportAnimal = false;
        $this->isDepartedAnimal = false;
        $this->updatedGeneDiversity = false;
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
        $this->pedigreeCountryCode = trim(strtoupper($pedigreeCountryCode));

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
        $this->pedigreeNumber = trim($pedigreeNumber);

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
     * @param string $nullFiller
     * @return null|string
     */
    public function getPedigreeString($nullFiller = null)
    {
        if(NullChecker::isNotNull($this->pedigreeCountryCode) && NullChecker::isNotNull($this->pedigreeNumber)) {
            return $this->pedigreeCountryCode.$this->pedigreeNumber;
        } else {
            return $nullFiller;
        }
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
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * Get full uln, country code + number
     *
     * @return string
     */
    public function getUln()
    {
        if($this->isUlnExists()) {
            return $this->ulnCountryCode . $this->ulnNumber;
        } else {
            return null;
        }
    }


    /**
     * @return bool
     */
    public function isUlnExists()
    {
        return NullChecker::isNotNull($this->ulnCountryCode) && NullChecker::isNotNull($this->ulnNumber);
    }


    /**
     * @return bool
     */
    public function isPedigreeExists()
    {
        return NullChecker::isNotNull($this->pedigreeCountryCode) && NullChecker::isNotNull($this->pedigreeNumber);
    }


    /**
     * @return string
     */
    public function getAnimalOrderNumber()
    {
        return $this->animalOrderNumber;
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
            $this->assignedTag->setTagStatus(TagStateType::ASSIGNING);
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
     *
     */
    public function removeAssignedTag()
    {
        $this->assignedTag->setAnimal(null);
        $this->assignedTag->setTagStatus(TagStateType::UNASSIGNED);
        $this->assignedTag = null;
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
        $this->name = trim($name);

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
        $this->gender = trim($gender);

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
     * Set parentFather
     *
     * @param Ram
     *
     * @return Ram
     */
    public function setParentFather(Ram $parentFather = null)
    {
        $this->parentFather = $parentFather;
        //$parentFather->getChildren()->add($this);

        return $this;
    }

    /**
     * Get parentFather
     *
     * @return Ram
     */
    public function getParentFather()
    {
        return $this->parentFather;
    }


    /**
     * @return int|null
     */
    public function getParentFatherId()
    {
        if($this->parentFather != null) {
            return $this->parentFather->getId();
        } else {
            return null;
        }
    }


    /**
     * @return int|null
     */
    public function getParentMotherId()
    {
        if($this->parentMother != null) {
            return $this->parentMother->getId();
        } else {
            return null;
        }
    }


    /**
     * Set parentMother
     *
     * @param Ewe $parentMother
     *
     * @return Ewe
     */
    public function setParentMother($parentMother = null)
    {
        $this->parentMother = $parentMother;
        //$parentMother->getChildren()->add($this);

        return $this;
    }

    /**
     * Get parentMother
     *
     * @return Ewe
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
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return Animal
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = trim($ulnNumber);

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
        $this->ulnCountryCode = trim(strtoupper($ulnCountryCode));

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
        $this->animalOrderNumber = trim($animalOrderNumber);

        return $this;
    }

    /**
     * Add export
     *
     * @param \AppBundle\Entity\DeclareExport $export
     *
     * @return Animal
     */
    public function addExport(\AppBundle\Entity\DeclareExport $export)
    {
        $this->exports[] = $export;

        return $this;
    }

    /*
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
     * Remove export
     *
     * @param \AppBundle\Entity\DeclareExport $export
     */
    public function removeExport(\AppBundle\Entity\DeclareExport $export)
    {
        $this->exports->removeElement($export);
    }

    /**
     * Get exports
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExports()
    {
        return $this->exports;
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
     * @param string $format
     * @return string
     */
    public function getDateOfDeathString($format = 'Y-m-d')
    {
        if ($this->dateOfDeath != null) {
            return $this->dateOfDeath->format($format);
        }
        return null;
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

    /*
     * Get dateOfBirth
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }


    /**
     * @param string $format
     * @return string
     */
    public function getDateOfBirthString($format = 'Y-m-d')
    {
        if($this->dateOfBirth != null) {
            return $this->dateOfBirth->format($format);
        }
        return null;
    }
    

    /**
     * Add flag
     *
     * @param \AppBundle\Entity\DeclareAnimalFlag $flag
     *
     * @return Animal
     */
    public function addFlag(\AppBundle\Entity\DeclareAnimalFlag $flag)
    {
        $this->flags[] = $flag;

        return $this;
    }

    /**
     * Remove flag
     *
     * @param \AppBundle\Entity\DeclareAnimalFlag $flag
     */
    public function removeFlag(\AppBundle\Entity\DeclareAnimalFlag $flag)
    {
        $this->flags->removeElement($flag);
    }

    /**
     * Get flags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return ArrayCollection
     */
    public function getDeclareWeights()
    {
        return $this->declareWeights;
    }

    /**
     * Add declareWeight
     *
     * @param \AppBundle\Entity\DeclareWeight $declareWeight
     *
     * @return Animal
     */
    public function addDeclareWeight(\AppBundle\Entity\DeclareWeight $declareWeight)
    {
        $this->births[] = $declareWeight;

        return $this;
    }

    /**
     * Remove declareWeight
     *
     * @param \AppBundle\Entity\DeclareWeight $declareWeight
     */
    public function removeDeclareWeight(\AppBundle\Entity\DeclareWeight $declareWeight)
    {
        $this->births->removeElement($declareWeight);
    }

    /**
     * Add birth
     *
     * @param \AppBundle\Entity\DeclareBirth $birth
     *
     * @return Animal
     */
    public function addBirth(\AppBundle\Entity\DeclareBirth $birth)
    {
        $this->births[] = $birth;

        return $this;
    }

    /**
     * Remove birth
     *
     * @param \AppBundle\Entity\DeclareBirth $birth
     */
    public function removeBirth(\AppBundle\Entity\DeclareBirth $birth)
    {
        $this->births->removeElement($birth);
    }

    /**
     * Get births
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBirths()
    {
        return $this->births;
    }

    /**
     * Add death
     *
     * @param \AppBundle\Entity\DeclareLoss $death
     *
     * @return Animal
     */
    public function addDeath(\AppBundle\Entity\DeclareLoss $death)
    {
        $this->deaths[] = $death;

        return $this;
    }

    /**
     * Remove death
     *
     * @param \AppBundle\Entity\DeclareLoss $death
     */
    public function removeDeath(\AppBundle\Entity\DeclareLoss $death)
    {
        $this->deaths->removeElement($death);
    }

    /**
     * Get deaths
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDeaths()
    {
        return $this->deaths;
    }

    /**
     * Set isImportAnimal
     *
     * @param boolean $isImportAnimal
     *
     * @return Animal
     */
    public function setIsImportAnimal($isImportAnimal)
    {
        $this->isImportAnimal = $isImportAnimal;

        return $this;
    }

    /**
     * Get isImportAnimal
     *
     * @return boolean
     */
    public function getIsImportAnimal()
    {
        return $this->isImportAnimal;
    }

    /**
     * Set isExportAnimal
     *
     * @param boolean $isExportAnimal
     *
     * @return Animal
     */
    public function setIsExportAnimal($isExportAnimal)
    {
        $this->isExportAnimal = $isExportAnimal;

        return $this;
    }

    /**
     * Get isExportAnimal
     *
     * @return boolean
     */
    public function getIsExportAnimal()
    {
        return $this->isExportAnimal;
    }

    /**
     * Set isDepartedAnimal
     *
     * @param boolean $isDepartedAnimal
     *
     * @return Animal
     */
    public function setIsDepartedAnimal($isDepartedAnimal)
    {
        $this->isDepartedAnimal = $isDepartedAnimal;

        return $this;
    }

    /**
     * Get isDepartedAnimal
     *
     * @return boolean
     */
    public function getIsDepartedAnimal()
    {
        return $this->isDepartedAnimal;
    }

    /**
     * Set animalCountryOrigin
     *
     * @param string $animalCountryOrigin
     *
     * @return Animal
     */
    public function setAnimalCountryOrigin($animalCountryOrigin)
    {
        $this->animalCountryOrigin = trim($animalCountryOrigin);

        return $this;
    }

    /**
     * Get animalCountryOrigin
     *
     * @return string
     */
    public function getAnimalCountryOrigin()
    {
        return $this->animalCountryOrigin;
    }

    /**
     * @return string
     */
    public function getTransferState()
    {
        return $this->transferState;
    }

    /**
     * @param string $transferState
     */
    public function setTransferState($transferState)
    {
        $this->transferState = trim($transferState);
    }

    /**
     * @return ArrayCollection
     */
    public function getAnimalResidenceHistory()
    {
        return $this->animalResidenceHistory;
    }

    /**
     * @param ArrayCollection $animalResidenceHistory
     */
    public function setAnimalResidenceHistory($animalResidenceHistory)
    {
        $this->animalResidenceHistory = $animalResidenceHistory;
    }

    /**
     * Add animalResidenceHistory
     *
     * @param AnimalResidence $animalResidenceHistory
     *
     * @return Animal
     */
    public function addAnimalResidenceHistory(AnimalResidence $animalResidenceHistory)
    {
        $this->animalResidenceHistory[] = $animalResidenceHistory;
    }

    /**
     * Add ulnHistory
     *
     * @param Tag $ulnHistory
     *
     * @return Animal
     */
    public function addUlnHistory(Tag $ulnHistory)
    {
        $this->ulnHistory[] = $ulnHistory;

        return $this;
    }

    /**
     * Remove ulnHistory
     *
     * @param Tag $ulnHistory
     */
    public function removeUlnHistory(Tag $ulnHistory)
    {
        $this->ulnHistory->removeElement($ulnHistory);
    }

    /**
     * Get ulnHistory
     *
     * @return Collection
     */
    public function getUlnHistory()
    {
            return $this->ulnHistory;
    }

    /**
     * @param $ulnCountryCode
     * @param $ulnNumber
     */
    public function replaceUln($ulnCountryCode , $ulnNumber) {

        //Get current set ulnCountryCode and ulnNumber, add it to the history.

        $tag = new Tag();
        $tag->setUlnCountryCode($this->getUlnCountryCode());
        $tag->setUlnNumber($this->getUlnNumber());
        $tag->setTagStatus("REPLACED");

        $this->ulnHistory->add($tag);

        //Set new ulnCountryCode and ulnNumber as the current.
        $this->setUlnCountryCode($ulnCountryCode);
        $this->setUlnNumber($ulnNumber);
    }



    /**
     * Add tagReplacement
     *
     * @param DeclareTagReplace $tagReplacement
     *
     * @return Animal
     */
    public function addTagReplacement(DeclareTagReplace $tagReplacement)
    {
        $this->tagReplacements[] = $tagReplacement;

        return $this;
    }

    /**
     * Remove animalResidenceHistory
     *
     * @param AnimalResidence $animalResidenceHistory
     */
    public function removeAnimalResidenceHistory(AnimalResidence $animalResidenceHistory)
    {
        $this->animalResidenceHistory->removeElement($animalResidenceHistory);
    }

    /**
     * Remove tagReplacement
     *
     * @param DeclareTagReplace $tagReplacement
     */
    public function removeTagReplacement(DeclareTagReplace $tagReplacement)
    {
        $this->tagReplacements->removeElement($tagReplacement);
    }

    /**
     * Get tagReplacements
     *
     * @return Collection
     */
    public function getTagReplacements()
    {
        return $this->tagReplacements;
    }

    /**
     * Add genderHistoryItem
     *
     * @param GenderHistoryItem $genderHistoryItem
     *
     * @return Animal
     */
    public function addGenderHistoryItem(GenderHistoryItem $genderHistoryItem)
    {
        $this->genderHistory[] = $genderHistoryItem;

        return $this;
    }

    /**
     * Remove genderHistoryItem
     *
     * @param GenderHistoryItem $genderHistoryItem
     */
    public function removeGenderHistoryItem(GenderHistoryItem $genderHistoryItem)
    {
        $this->genderHistory->removeElement($genderHistoryItem);
    }

    /**
     * Get genderHistory
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGenderHistory()
    {
        return $this->genderHistory;
    }

    /**
     * Add bodyFatMeasurement
     *
     * @param \AppBundle\Entity\BodyFat $bodyFatMeasurement
     *
     * @return Animal
     */
    public function addBodyFatMeasurement(\AppBundle\Entity\BodyFat $bodyFatMeasurement)
    {
        $this->bodyFatMeasurements[] = $bodyFatMeasurement;

        return $this;
    }

    /**
     * Remove bodyFatMeasurement
     *
     * @param \AppBundle\Entity\BodyFat $bodyFatMeasurement
     */
    public function removeBodyFatMeasurement(\AppBundle\Entity\BodyFat $bodyFatMeasurement)
    {
        $this->bodyFatMeasurements->removeElement($bodyFatMeasurement);
    }

    /**
     * Get bodyFatMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBodyFatMeasurements()
    {
        return $this->bodyFatMeasurements;
    }

    /**
     * Add muscleThicknessMeasurement
     *
     * @param \AppBundle\Entity\MuscleThickness $muscleThicknessMeasurement
     *
     * @return Animal
     */
    public function addMuscleThicknessMeasurement(\AppBundle\Entity\MuscleThickness $muscleThicknessMeasurement)
    {
        $this->muscleThicknessMeasurements[] = $muscleThicknessMeasurement;

        return $this;
    }

    /**
     * Remove muscleThicknessMeasurement
     *
     * @param \AppBundle\Entity\MuscleThickness $muscleThicknessMeasurement
     */
    public function removeMuscleThicknessMeasurement(\AppBundle\Entity\MuscleThickness $muscleThicknessMeasurement)
    {
        $this->muscleThicknessMeasurements->removeElement($muscleThicknessMeasurement);
    }

    /**
     * Get muscleThicknessMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMuscleThicknessMeasurements()
    {
        return $this->muscleThicknessMeasurements;
    }

    /**
     * Add tailLengthMeasurement
     *
     * @param \AppBundle\Entity\TailLength $tailLengthMeasurement
     *
     * @return Animal
     */
    public function addTailLengthMeasurement(\AppBundle\Entity\TailLength $tailLengthMeasurement)
    {
        $this->tailLengthMeasurements[] = $tailLengthMeasurement;

        return $this;
    }

    /**
     * Remove tailLengthMeasurement
     *
     * @param \AppBundle\Entity\TailLength $tailLengthMeasurement
     */
    public function removeTailLengthMeasurement(\AppBundle\Entity\TailLength $tailLengthMeasurement)
    {
        $this->tailLengthMeasurements->removeElement($tailLengthMeasurement);
    }

    /**
     * Get tailLengthMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTailLengthMeasurements()
    {
        return $this->tailLengthMeasurements;
    }

    /**
     * Add weightMeasurement
     *
     * @param \AppBundle\Entity\Weight $weightMeasurement
     *
     * @return Animal
     */
    public function addWeightMeasurement(\AppBundle\Entity\Weight $weightMeasurement)
    {
        $this->weightMeasurements[] = $weightMeasurement;

        return $this;
    }

    /**
     * Remove weightMeasurement
     *
     * @param \AppBundle\Entity\Weight $weightMeasurement
     */
    public function removeWeightMeasurement(\AppBundle\Entity\Weight $weightMeasurement)
    {
        $this->weightMeasurements->removeElement($weightMeasurement);
    }

    /**
     * Get weightMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWeightMeasurements()
    {
        return $this->weightMeasurements;
    }

    /**
     * @return string
     */
    public function getBreed()
    {
        return $this->breed;
    }

    /**
     * @param string $breed
     */
    public function setBreed($breed)
    {
        $this->breed = trim($breed);
    }

    /**
     * Set breedType
     *
     * @param string $breedType
     *
     * @return Animal
     */
    public function setBreedType($breedType)
    {
        $this->breedType = trim($breedType);

        return $this;
    }

    /**
     * Get breedType
     *
     * @return string
     */
    public function getBreedType()
    {
        return $this->breedType;
    }

    /**
     * Set breedCode
     *
     * @param string $breedCode
     *
     * @return Animal
     */
    public function setBreedCode($breedCode)
    {
        $this->breedCode = trim($breedCode);

        return $this;
    }

    /**
     * Get breedCode
     *
     * @return string
     */
    public function getBreedCode()
    {
        return $this->breedCode;
    }

    /**
     * @return boolean|null
     */
    public function hasCompleteBreedCode()
    {
        return $this->hasCompleteBreedCode;
    }

    /**
     * @param boolean|null $hasCompleteBreedCode
     * @return Animal
     */
    public function setHasCompleteBreedCode($hasCompleteBreedCode)
    {
        $this->hasCompleteBreedCode = $hasCompleteBreedCode;
        return $this;
    }

    /**
     * @return float
     */
    public function getHeterosis()
    {
        return $this->heterosis;
    }

    /**
     * @param float $heterosis
     * @return Animal
     */
    public function setHeterosis($heterosis)
    {
        $this->heterosis = $heterosis;
        return $this;
    }

    /**
     * @return float
     */
    public function getRecombination()
    {
        return $this->recombination;
    }

    /**
     * @param float $recombination
     * @return Animal
     */
    public function setRecombination($recombination)
    {
        $this->recombination = $recombination;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUpdatedGeneDiversity()
    {
        return $this->updatedGeneDiversity;
    }

    /**
     * @param boolean $updatedGeneDiversity
     * @return Animal
     */
    public function setUpdatedGeneDiversity($updatedGeneDiversity)
    {
        $this->updatedGeneDiversity = $updatedGeneDiversity;
        return $this;
    }

    /**
     * @return string
     */
    public function getPredicate()
    {
        return $this->predicate;
    }

    /**
     * @param string $predicate
     */
    public function setPredicate($predicate)
    {
        $this->predicate = $predicate;
    }

    /**
     * @return int
     */
    public function getPredicateScore()
    {
        return $this->predicateScore;
    }

    /**
     * @param int $predicateScore
     */
    public function setPredicateScore($predicateScore)
    {
        $this->predicateScore = $predicateScore;
    }

    /**
     * Set scrapieGenotype
     *
     * @param string $scrapieGenotype
     *
     * @return Animal
     */
    public function setScrapieGenotype($scrapieGenotype)
    {
        $this->scrapieGenotype = trim($scrapieGenotype);

        return $this;
    }

    /**
     * Get scrapieGenotype
     *
     * @return string
     */
    public function getScrapieGenotype()
    {
        return $this->scrapieGenotype;
    }

    /**
     * @return string
     */
    public function getBlindnessFactor()
    {
        return $this->blindnessFactor;
    }

    /**
     * @param string $blindnessFactor
     */
    public function setBlindnessFactor($blindnessFactor)
    {
        $this->blindnessFactor = $blindnessFactor;
    }

    /**
     * @return string
     */
    public function getMyoMax()
    {
        return $this->myoMax;
    }

    /**
     * @param string $myoMax
     */
    public function setMyoMax($myoMax)
    {
        $this->myoMax = $myoMax;
    }    

    /**
     * Add exteriorMeasurement
     *
     * @param \AppBundle\Entity\Exterior $exteriorMeasurement
     *
     * @return Animal
     */
    public function addExteriorMeasurement(\AppBundle\Entity\Exterior $exteriorMeasurement)
    {
        $this->exteriorMeasurements[] = $exteriorMeasurement;

        return $this;
    }

    /**
     * Remove exteriorMeasurement
     *
     * @param \AppBundle\Entity\Exterior $exteriorMeasurement
     */
    public function removeExteriorMeasurement(\AppBundle\Entity\Exterior $exteriorMeasurement)
    {
        $this->exteriorMeasurements->removeElement($exteriorMeasurement);
    }

    /**
     * Get exteriorMeasurements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExteriorMeasurements()
    {
        return $this->exteriorMeasurements;
    }

    /**
     * @return Litter
     */
    public function getLitter()
    {
        return $this->litter;
    }

    /**
     * @param Litter $litter
     */
    public function setLitter($litter)
    {
        $this->litter = $litter;
    }

    /**
     * @return Client
     */
    public function getBreeder()
    {
        return $this->breeder;
    }

    /**
     * @param Client $breeder
     */
    public function setBreeder($breeder)
    {
        $this->breeder = $breeder;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Add parent
     *
     * @param \AppBundle\Entity\Animal $parent
     *
     * @return Animal
     */
    public function addParent(\AppBundle\Entity\Animal $parent)
    {
        $this->parents[] = $parent;

        return $this;
    }

    /**
     * Remove parent
     *
     * @param \AppBundle\Entity\Animal $parent
     */
    public function removeParent(\AppBundle\Entity\Animal $parent)
    {
        $this->parents->removeElement($parent);
    }

    /**
     * Get parents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParents()
    {
        return $this->parents;
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
     * @return BreedCodes
     */
    public function getBreedCodes()
    {
        return $this->breedCodes;
    }

    /**
     * @param BreedCodes $breedCodes
     */
    public function setBreedCodes($breedCodes)
    {
        $this->breedCodes = $breedCodes;
    }

    /**
     * @return string
     */
    public function getUbnOfBirth()
    {
        return $this->ubnOfBirth;
    }


    /**
     * @return string
     */
    public function getUbn()
    {
        if($this->location instanceof Location) {
          return $this->location->getUbn();
        } else {
            return null;
        }
    }


    /**
     * @param string $ubnOfBirth
     */
    public function setUbnOfBirth($ubnOfBirth)
    {
        $this->ubnOfBirth = trim($ubnOfBirth);
    }

    /**
     * @return Location
     */
    public function getLocationOfBirth()
    {
        return $this->locationOfBirth;
    }

    /**
     * @param Location $locationOfBirth
     */
    public function setLocationOfBirth($locationOfBirth)
    {
        $this->locationOfBirth = $locationOfBirth;
    }

    /**
     * @return PedigreeRegister
     */
    public function getPedigreeRegister()
    {
        return $this->pedigreeRegister;
    }

    /**
     * @param string $nullFiller
     * @return string
     */
    public function getPedigreeRegisterFullName($nullFiller = '')
    {
        if($this->pedigreeRegister != null) {
            $registerName = $this->pedigreeRegister->getFullName();
            if($registerName != null && $registerName != '') {
                return $registerName;
            }
        }
        return $nullFiller;
    }

    /**
     * @param PedigreeRegister $pedigreeRegister
     */
    public function setPedigreeRegister($pedigreeRegister)
    {
        $this->pedigreeRegister = $pedigreeRegister;
    }


    /**
     * @return string
     */
    public function getBirthProgress()
    {
        return $this->birthProgress;
    }

    /**
     * @param string $birthProgress
     */
    public function setBirthProgress($birthProgress)
    {
        $this->birthProgress = $birthProgress;
    }

    /**
     * @return boolean
     */
    public function getLambar()
    {
        return $this->lambar;
    }

    /**
     * @param boolean $lambar
     */
    public function setLambar($lambar)
    {
        $this->lambar = $lambar;
    }


    /**
     * Add breedValues
     *
     * @param BreedValuesSet $breedValuesSet
     *
     * @return Animal
     */
    public function addBreedValuesSet(BreedValuesSet $breedValuesSet)
    {
        $this->breedValuesSets[] = $breedValuesSet;

        return $this;
    }

    /**
     * Remove breedValues
     *
     * @param BreedValuesSet $breedValuesSet
     */
    public function removeBreedValuesSet(BreedValuesSet $breedValuesSet)
    {
        $this->breedValuesSets->removeElement($breedValuesSet);
    }

    /**
     * Get BreedValuesSets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBreedValuesSets()
    {
        return $this->breedValuesSets;
    }


    /**
     * @return BreedValuesSet|null
     */
    public function getLastBreedValuesSet()
    {
        if(count($this->breedValuesSets) > 0) {
            return $this->breedValuesSets->last();
        } else {
            return null;
        }
    }

    /**
     * @return ResultTableBreedGrades
     */
    public function getLatestBreedGrades()
    {
        return $this->latestBreedGrades;
    }

    /**
     * @param ResultTableBreedGrades $latestBreedGrades
     * @return Animal
     */
    public function setLatestBreedGrades($latestBreedGrades)
    {
        $this->latestBreedGrades = $latestBreedGrades;
        return $this;
    }


    /**
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @param string $nickname
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
    }

    /**
     * @return bool
     */
    public function isAnimalPublic()
    {
        $location = $this->getLocation();
        if($location != null) {
            $company = $location->getCompany();
            if($company != null) {
                return $company->getIsRevealHistoricAnimals();
            }
        }
        return true;
    }



    /**
     * @return string
     */
    public function getCollarColor() {
        return $this->collarColor;
    }

    /**
     * @param string $collarColor
     */
    public function setCollarColor($collarColor) {
        $this->collarColor = $collarColor;
    }

    /**
     * @return string
     */
    public function getCollarNumber() {
        return $this->collarNumber;
    }

    /**
     * @param string $collarNumber
     */
    public function setCollarNumber($collarNumber) {
        $this->collarNumber = $collarNumber;
    }
}
