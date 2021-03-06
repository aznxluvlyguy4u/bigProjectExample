<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Location
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class Location
{
    use EntityClassInfo;

  /**
   * @var integer
   *
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="IDENTITY")
   * @Expose
   * @JMS\Type("integer")
   * @JMS\Groups({
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY",
   *     "RESPONSE_PERSISTENCE",
   *     "RVO",
   *     "TREATMENT",
   *     "TREATMEN_MIN",
   *     "TREATMENT_TEMPLATE"
   * })
   *
   */
  protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT",
     *     "BASIC",
     *     "EDIT_OVERVIEW",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "MINIMAL",
     *     "RESPONSE_PERSISTENCE",
     *     "RVO",
     *     "DOSSIER"
     * })
     * @Expose
     */
    private $locationId;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=false)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $creationDate;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $lastResidenceFixDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT",
     *     "BASIC",
     *     "EDIT_OVERVIEW",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "LIVESTOCK",
     *     "MINIMAL",
     *     "RESPONSE_PERSISTENCE",
     *     "RVO",
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN",
     *     "UBN",
     *     "DOSSIER"
     * })
     * @Expose
     */
    protected $ubn;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "BASIC",
     *     "DOSSIER"
     * })
     * @Expose
     */
    private $locationHolder;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="DeclareArrival", mappedBy="location")
     * @ORM\OrderBy({"arrivalDate" = "ASC"})
     */
    protected $arrivals;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareBirth", mappedBy="location")
     * @ORM\OrderBy({"dateOfBirth" = "ASC"})
     */
    protected $births;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareDepart", mappedBy="location")
     * @ORM\OrderBy({"departDate" = "ASC"})
     */
    protected $departures;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="location")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    protected $animals;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareImport", mappedBy="location")
     * @ORM\OrderBy({"importDate" = "ASC"})
     */
    protected $imports;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareExport", mappedBy="location")
     * @ORM\OrderBy({"exportDate" = "ASC"})
     */
    protected $exports;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareTagsTransfer", mappedBy="location")
     */
    protected $tagTransfers;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareLoss", mappedBy="location")
     * @ORM\OrderBy({"dateOfDeath" = "ASC"})
     */
    protected $losses;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("AppBundle\Entity\DeclareAnimalFlag")
     * @ORM\OneToMany(targetEntity="DeclareAnimalFlag", mappedBy="location", cascade={"persist"})
     */
    protected $flags;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("AppBundle\Entity\Mate")
     * @ORM\OneToMany(targetEntity="Mate", mappedBy="location", cascade={"persist"})
     * @ORM\OrderBy({"startDate" = "ASC"})
     */
    protected $matings;


    /**
     * @var ArrayCollection
     *
     * @JMS\Type("AppBundle\Entity\DeclareWeight")
     * @ORM\OneToMany(targetEntity="DeclareWeight", mappedBy="location", cascade={"persist"})
     * @ORM\OrderBy({"measurementDate" = "ASC"})
     */
    protected $declareWeights;


    /**
     * @var Company
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="locations", cascade={"persist"}, fetch="EAGER")
     * @JMS\Type("AppBundle\Entity\Company")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT",
     * })
     * @JMS\MaxDepth(depth=2)
     * @Expose
     */
    protected $company;

    /**
     * @var LocationAddress
     *
     * @ORM\OneToOne(targetEntity="LocationAddress", cascade={"persist"})
     * @Expose
     * @JMS\Type("AppBundle\Entity\LocationAddress")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "DOSSIER"
     * })
     */
    private $address;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
     * @ORM\OneToMany(targetEntity="RevokeDeclaration", mappedBy="location", cascade={"persist"})
     */
    protected $revokes;

    /**
     * @ORM\OneToOne(targetEntity="LocationHealth", inversedBy="location")
     * @JMS\Type("AppBundle\Entity\LocationHealth")
     */
    private $locationHealth;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="LocationHealthMessage", mappedBy="location")
     * @ORM\JoinColumn(name="health_message_id", referencedColumnName="id", nullable=true)
     * @ORM\OrderBy({"arrivalDate" = "ASC"})
     * @JMS\Type("AppBundle\Entity\LocationHealthMessage")
     */
    private $healthMessages;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AnimalResidence", mappedBy="location")
     * @JMS\Type("AppBundle\Entity\AnimalResidence")
     */
    private $animalResidenceHistory;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="LocationHealthInspection", mappedBy="location")
     * @JMS\Type("AppBundle\Entity\LocationHealthInspection")
     */
    private $inspections;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="location", cascade={"persist"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Tag>")
     */
    private $tags;

    /**
     * @var ArrayCollection
     * @ORM\OrderBy({"description" = "ASC"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\TreatmentTemplate", mappedBy="location", cascade={"persist"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\TreatmentTemplate>")
     */
    private $treatmentTemplates;

    /**
     * @var ArrayCollection
     * @ORM\OrderBy({"description" = "ASC"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\TreatmentLocation", mappedBy="location", cascade={"persist", "remove"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\TreatmentLocation>")
     */
    private $treatments;

    /**
     * @var ArrayCollection
     * @ORM\OrderBy({"breederNumber" = "ASC"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PedigreeRegisterRegistration", mappedBy="location", cascade={"persist", "remove"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\PedigreeRegisterRegistration>")
     */
    private $pedigreeRegisterRegistrations;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT",
     *     "BASIC",
     *     "INVOICE",
     *     "MINIMAL",
     *     "TREATMENT_TEMPLATE",
     *     "DOSSIER"
     * })
     * @Expose
     */
    private $isActive;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Worker", mappedBy="location", cascade={"persist", "remove"}, fetch="LAZY")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Worker>")
     */
    private $workers;

    /**
     * @var ArrayCollection|AnimalAnnotation[]
     * @ORM\OrderBy({"updatedAt" = "DESC"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AnimalAnnotation", mappedBy="location", cascade={"persist", "remove"}, fetch="LAZY")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\AnimalAnnotation>")
     */
    private $animalAnnotations;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("owner")
     * @JMS\Groups({
     *     "GHOST_LOGIN"
     * })
     * @JMS\Type("AppBundle\Entity\Client")
     */
    public function getOwner()
    {
        if($this->company != null) {
            return $this->getCompany()->getOwner();
        }
        return null;
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("users")
     * @JMS\Groups({
     *     "GHOST_LOGIN"
     * })
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Person>")
     */
    public function getCompanyUsers() {
        if($this->company != null) {
            return $this->getCompany()->getCompanyUsers();
        }
        return new ArrayCollection();
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("has_ghost_login")
     * @JMS\Groups({
     *     "GHOST_LOGIN"
     * })
     * @JMS\Type("boolean")
     * @return boolean
     */
    public function hasGhostLoginOption() {
        if (!$this->isActive || !$this->getCompany() || !$this->getCompany()->isActive()) {
            return false;
        }

        return $this->getOwner() !== null || count($this->getCompanyUsers()) > 0;
    }


    /**
     * @var ResultTableAnimalCounts|null
     * @ORM\OneToOne(targetEntity="ResultTableAnimalCounts", mappedBy="location", cascade={"persist", "remove"})
     * @JMS\Type("AppBundle\Entity\ResultTableAnimalCounts")
     */
    private $resultTableAnimalCounts;

    /**
     * @var ArrayCollection|AnimalRelocation[]
     *
     * @ORM\OneToMany(targetEntity="AnimalRelocation", mappedBy="location", cascade={"persist", "remove"}, fetch="LAZY")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\AnimalRelocation>")
     */
    private $animalRelocations;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->arrivals = new ArrayCollection();
        $this->births = new ArrayCollection();
        $this->departures = new ArrayCollection();
        $this->imports = new ArrayCollection();
        $this->exports = new ArrayCollection();
        $this->losses = new ArrayCollection();
        $this->animals = new ArrayCollection();
        $this->tagTransfers = new ArrayCollection();
        $this->flags = new ArrayCollection();
        $this->revokes = new ArrayCollection();
        $this->matings = new ArrayCollection();
        $this->declareWeights = new ArrayCollection();
        $this->healthMessages = new ArrayCollection();
        $this->animalResidenceHistory = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->treatmentTemplates = new ArrayCollection();
        $this->treatments = new ArrayCollection();
        $this->pedigreeRegisterRegistrations = new ArrayCollection();
        $this->workers = new ArrayCollection();
        $this->animalRelocations = new ArrayCollection();
        $this->animalAnnotations = new ArrayCollection();
        $this->setLocationId(Utils::generateTokenCode());

        $this->creationDate = new \DateTime();
    }

    /**
     * @return string
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param string $locationId
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * Add arrival
     *
     * @param \AppBundle\Entity\DeclareArrival $arrival
     *
     * @return Location
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
     * @return Collection
     */
    public function getArrivals()
    {
        return $this->arrivals;
    }

    /**
     * Add birth
     *
     * @param \AppBundle\Entity\DeclareBirth $birth
     *
     * @return Location
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
     * @return Collection
     */
    public function getBirths()
    {
        return $this->births;
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
     * Set ubn
     *
     * @param string $ubn
     *
     * @return Location
     */
    public function setUbn($ubn)
    {
        $this->ubn = trim($ubn);

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
     * Set company
     *
     * @param \AppBundle\Entity\Company $company
     *
     * @return Location
     */
    public function setCompany(\AppBundle\Entity\Company $company = null)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return \AppBundle\Entity\Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set address
     *
     * @param \AppBundle\Entity\LocationAddress $address
     *
     * @return Location
     */
    public function setAddress(\AppBundle\Entity\LocationAddress $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \AppBundle\Entity\LocationAddress
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Add import
     *
     * @param \AppBundle\Entity\DeclareImport $import
     *
     * @return Location
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
     * @return Collection
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * Add departure
     *
     * @param \AppBundle\Entity\DeclareDepart $departure
     *
     * @return Location
     */
    public function addDeparture(\AppBundle\Entity\DeclareDepart $departure)
    {
        $this->departures[] = $departure;

        return $this;
    }

    /**
     * Remove departure
     *
     * @param \AppBundle\Entity\DeclareDepart $departure
     */
    public function removeDeparture(\AppBundle\Entity\DeclareDepart $departure)
    {
        $this->departures->removeElement($departure);
    }

    /**
     * Get departures
     *
     * @return Collection
     */
    public function getDepartures()
    {
        return $this->departures;
    }

    /**
     * Add loss
     *
     * @param \AppBundle\Entity\DeclareLoss $loss
     *
     * @return Location
     */
    public function addLoss(\AppBundle\Entity\DeclareLoss $loss)
    {
        $this->losses[] = $loss;

        return $this;
    }

    /**
     * Remove loss
     *
     * @param \AppBundle\Entity\DeclareLoss $loss
     */
    public function removeLoss(\AppBundle\Entity\DeclareLoss $loss)
    {
        $this->losses->removeElement($loss);
    }

    /**
     * Get losses
     *
     * @return Collection
     */
    public function getLosses()
    {
        return $this->losses;
    }

    /**
     * Add animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return Location
     */
    public function addAnimal(\AppBundle\Entity\Animal $animal)
    {
        $animal->setLocation($this);
        $this->animals[] = $animal;

        return $this;
    }

    /**
     * Remove animal
     *
     * @param \AppBundle\Entity\Animal $animal
     */
    public function removeAnimal(\AppBundle\Entity\Animal $animal)
    {
        $this->animals->removeElement($animal);
    }

    /**
     * Get animals
     * @return Collection|Animal[]
     */
    public function getAnimals()
    {
        return $this->animals;
    }

    /**
     * Add export
     *
     * @param \AppBundle\Entity\DeclareExport $export
     *
     * @return Location
     */
    public function addExport(\AppBundle\Entity\DeclareExport $export)
    {
        $this->exports[] = $export;

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
     * @return Collection
     */
    public function getExports()
    {
        return $this->exports;
    }


    /**
     * Add tagTransfer
     *
     * @param \AppBundle\Entity\DeclareTagsTransfer $tagTransfer
     *
     * @return Location
     */
    public function addTagTransfer(\AppBundle\Entity\DeclareTagsTransfer $tagTransfer)
    {
        $this->tagTransfers[] = $tagTransfer;

        return $this;
    }

    /**
     * Remove tagTransfer
     *
     * @param \AppBundle\Entity\DeclareTagsTransfer $tagTransfer
     */
    public function removeTagTransfer(\AppBundle\Entity\DeclareTagsTransfer $tagTransfer)
    {
        $this->tagTransfers->removeElement($tagTransfer);
    }

    /**
     * Get tagTransfers
     *
     * @return Collection
     */
    public function getTagTransfers()
    {
        return $this->tagTransfers;
    }

    /**
     * Add flag
     *
     * @param \AppBundle\Entity\DeclareAnimalFlag $flag
     *
     * @return Location
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
     * @return Collection
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Add revoke
     *
     * @param \AppBundle\Entity\RevokeDeclaration $revoke
     *
     * @return Location
     */
    public function addRevoke(\AppBundle\Entity\RevokeDeclaration $revoke)
    {
        $this->revokes[] = $revoke;

        return $this;
    }

    /**
     * Remove revoke
     *
     * @param \AppBundle\Entity\RevokeDeclaration $revoke
     */
    public function removeRevoke(\AppBundle\Entity\RevokeDeclaration $revoke)
    {
        $this->revokes->removeElement($revoke);
    }

    /**
     * Get revokes
     *
     * @return Collection
     */
    public function getRevokes()
    {
        return $this->revokes;
    }


    /**
     * Add mate
     *
     * @param Mate $mate
     *
     * @return Location
     */
    public function addMate(Mate $mate)
    {
        $this->matings[] = $mate;

        return $this;
    }

    /**
     * Remove mate
     *
     * @param Mate $mate
     */
    public function removeMate(Mate $mate)
    {
        $this->matings->removeElement($mate);
    }

    /**
     * Get matings
     *
     * @return Collection
     */
    public function getMatings()
    {
        return $this->matings;
    }

    /**
     * @return DeclareWeight
     */
    public function getDeclareWeights()
    {
        return $this->declareWeights;
    }

    /**
     * Add DeclareWeight
     *
     * @param DeclareWeight $declareWeight
     *
     * @return Location
     */
    public function addDeclareWeight(DeclareWeight $declareWeight)
    {
        $this->declareWeights[] = $declareWeight;

        return $this;
    }

    /**
     * Remove DeclareWeight
     *
     * @param DeclareWeight $declareWeight
     */
    public function removeDeclareWeight(DeclareWeight $declareWeight)
    {
        $this->declareWeights->removeElement($declareWeight);
    }

    /**
     * Set locationHolder
     *
     * @param string $locationHolder
     *
     * @return Location
     */
    public function setLocationHolder($locationHolder)
    {
        $this->locationHolder = $locationHolder;

        return $this;
    }

    /**
     * Get locationHolder
     *
     * @return string
     */
    public function getLocationHolder()
    {
        return $this->locationHolder;
    }

    /**
     * Add healthMessage
     *
     * @param \AppBundle\Entity\LocationHealthMessage $healthMessage
     *
     * @return Location
     */
    public function addHealthMessage(\AppBundle\Entity\LocationHealthMessage $healthMessage)
    {
        $this->healthMessages[] = $healthMessage;

        return $this;
    }

    /**
     * Remove healthMessage
     *
     * @param \AppBundle\Entity\LocationHealthMessage $healthMessage
     */
    public function removeHealthMessage(\AppBundle\Entity\LocationHealthMessage $healthMessage)
    {
        $this->healthMessages->removeElement($healthMessage);
    }

    /**
     * Get healthMessages
     *
     * @return Collection
     */
    public function getHealthMessages()
    {
        return $this->healthMessages;
    }

    /**
     * Set locationHealth
     *
     * @param \AppBundle\Entity\LocationHealth $locationHealth
     *
     * @return Location
     */
    public function setLocationHealth(\AppBundle\Entity\LocationHealth $locationHealth = null)
    {
        $this->locationHealth = $locationHealth;

        return $this;
    }

    /**
     * Get locationHealth
     *
     * @return \AppBundle\Entity\LocationHealth
     */
    public function getLocationHealth() {
        return $this->locationHealth;
    }

    /**
     * Add animalResidenceHistory
     *
     * @param AnimalResidence $animalResidenceHistory
     *
     * @return Location
     */
    public function addAnimalResidenceHistory(AnimalResidence $animalResidenceHistory)
    {
        $this->animalResidenceHistory[] = $animalResidenceHistory;

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
     * Get animalResidenceHistory
     *
     * @return Collection
     */
    public function getAnimalResidenceHistory()
    {
        return $this->animalResidenceHistory;
    }

    /**
     * @return ArrayCollection
     */
    public function getInspections()
    {
        return $this->inspections;
    }

    /**
     * @param ArrayCollection $inspections
     */
    public function setInspections($inspections)
    {
        $this->inspections = $inspections;
    }


    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param ArrayCollection $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * Add tag
     *
     * @param Tag $tag
     *
     * @return Location
     */
    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }


    /**
     * @return ArrayCollection
     */
    public function getTreatmentTemplates()
    {
        return $this->treatmentTemplates;
    }

    /**
     * @param ArrayCollection $treatmentTemplates
     * @return Location
     */
    public function setTreatmentTemplates($treatmentTemplates)
    {
        $this->treatmentTemplates = $treatmentTemplates;
        return $this;
    }

    /**
     * Add treatmentTemplate
     * @param TreatmentTemplate $treatmentTemplate
     * @return Location
     */
    public function addTreatmentTemplate(TreatmentTemplate $treatmentTemplate)
    {
        $this->treatmentTemplates->add($treatmentTemplate);
        return $this;
    }

    /**
     * Remove treatmentTemplate
     * @param TreatmentTemplate $treatmentTemplate
     * @return Location
     */
    public function removeTreatmentTemplate(TreatmentTemplate $treatmentTemplate)
    {
        $this->treatmentTemplates->removeElement($treatmentTemplate);
        return $this;
    }


    /**
     * @return ArrayCollection
     */
    public function getTreatments()
    {
        return $this->treatments;
    }

    /**
     * @param ArrayCollection $treatments
     * @return Location
     */
    public function setTreatments($treatments)
    {
        $this->treatments = $treatments;
        return $this;
    }

    /**
     * Add treatment
     * @param TreatmentLocation $treatment
     * @return Location
     */
    public function addTreatment(TreatmentLocation $treatment)
    {
        $this->treatments->add($treatment);
        return $this;
    }

    /**
     * Remove treatment
     * @param TreatmentLocation $treatment
     * @return Location
     */
    public function removeTreatment(TreatmentLocation $treatment)
    {
        $this->treatments->removeElement($treatment);
        return $this;
    }

    /**
     * @return ArrayCollection|PedigreeRegisterRegistration[]
     */
    public function getPedigreeRegisterRegistrations()
    {
        return $this->pedigreeRegisterRegistrations;
    }

    /**
     * @param ArrayCollection $pedigreeRegisterRegistrations
     * @return Location
     */
    public function setPedigreeRegisterRegistrations($pedigreeRegisterRegistrations)
    {
        $this->pedigreeRegisterRegistrations = $pedigreeRegisterRegistrations;
        return $this;
    }

    /**
     * @param PedigreeRegisterRegistration $pedigreeRegisterRegistration
     * @return Location
     */
    public function addPedigreeRegisterRegistration(PedigreeRegisterRegistration $pedigreeRegisterRegistration)
    {
        $this->pedigreeRegisterRegistrations->add($pedigreeRegisterRegistration);
        return $this;
    }

    /**
     * @param PedigreeRegisterRegistration $pedigreeRegisterRegistration
     * @return Location
     */
    public function removePedigreeRegisterRegistration(PedigreeRegisterRegistration $pedigreeRegisterRegistration)
    {
        $this->pedigreeRegisterRegistrations->removeElement($pedigreeRegisterRegistration);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param mixed $isActive
     * @return Location
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }


    /**
     * @return bool
     */
    public function getAnimalHealthSubscription()
    {
        return $this->company && $this->company->getAnimalHealthSubscription();
    }


    /**
     * @return bool
     */
    public function isDutchLocation(): bool
    {
        if ($this->getAddress()) {
            return $this->getAddress()->isDutchAddress();
        }
        return Address::IS_DUTCH_COUNTRY_DEFAULT_BOOLEAN;
    }


    /**
     * @return bool
     */
    public function hasNonBlankScrapieStatus(): bool
    {
        return $this->getLocationHealth() && $this->getLocationHealth()->hasNonBlankScrapieStatus();
    }


    /**
     * @return null|string
     */
    public function getCountryCode(): ?string
    {
        if ($this->getAddress()) {
            return $this->getAddress()->getCountryCode();
        }
        return null;
    }


    /**
     * @return Country|null
     */
    public function getCountryDetails(): ?Country
    {
        if ($this->getAddress()) {
            return $this->getAddress()->getCountryDetails();
        }
        return null;
    }


    /**
     * @param bool $returnEmptyStringAsNull
     * @return null|string
     */
    public function getCompanyName($returnEmptyStringAsNull = false): ?string
    {
        $companyName = $this->getCompany() ? $this->getCompany()->getCompanyName() : null;
        return $returnEmptyStringAsNull && empty($companyName) ? null : $companyName;
    }

    /**
     * @return ResultTableAnimalCounts|null
     */
    public function getResultTableAnimalCounts(): ?ResultTableAnimalCounts
    {
        return $this->resultTableAnimalCounts;
    }

    /**
     * @param ResultTableAnimalCounts|null $resultTableAnimalCounts
     * @return Location
     */
    public function setResultTableAnimalCounts(?ResultTableAnimalCounts $resultTableAnimalCounts): Location
    {
        $this->resultTableAnimalCounts = $resultTableAnimalCounts;
        return $this;
    }

    /**
     * @return AnimalRelocation[]|ArrayCollection
     */
    public function getAnimalRelocations()
    {
        return $this->animalRelocations;
    }

    /**
     * @param AnimalRelocation[]|ArrayCollection $animalRelocations
     * @return Location
     */
    public function setAnimalRelocations($animalRelocations)
    {
        $this->animalRelocations = $animalRelocations;
        return $this;
    }

    /**
     * @return AnimalAnnotation[]|ArrayCollection
     */
    public function getAnimalAnnotations()
    {
        return $this->animalAnnotations;
    }

    /**
     * @param  AnimalAnnotation[]|ArrayCollection  $annotations
     * @return Location
     */
    public function setAnimalAnnotations(ArrayCollection $annotations)
    {
        $this->animalAnnotations = $annotations;
        return $this;
    }

    /**
     * Add annotation
     *
     * @param AnimalAnnotation $annotation
     *
     * @return Location
     */
    public function addAnimalAnnotation(AnimalAnnotation $annotation)
    {
        $this->animalAnnotations->add($annotation);
        return $this;
    }

    /**
     * Remove annotation
     *
     * @param AnimalAnnotation $annotation
     */
    public function removeAnimalAnnotation(AnimalAnnotation $annotation)
    {
        $this->animalAnnotations->removeElement($annotation);
    }

    /**
     * @return DateTime
     */
    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param DateTime $creationDate
     * @return Location
     */
    public function setCreationDate(DateTime $creationDate): Location
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastResidenceFixDate(): ?DateTime
    {
        return $this->lastResidenceFixDate;
    }

    /**
     * @param DateTime|null $lastResidenceFixDate
     * @return Location
     */
    public function setLastResidenceFixDate(?DateTime $lastResidenceFixDate): Location
    {
        $this->lastResidenceFixDate = $lastResidenceFixDate;
        return $this;
    }


}
