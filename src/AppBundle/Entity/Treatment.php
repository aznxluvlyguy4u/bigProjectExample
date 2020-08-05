<?php

namespace AppBundle\Entity;


use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\Translation;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Treatment
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TreatmentRepository")
 * @package AppBundle\Entity
 */
class Treatment implements TreatmentInterface
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $id;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="treatments")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $location;

    /**
     * @var Animal[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Animal", inversedBy="treatments", cascade={"remove"}, fetch="LAZY")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Animal>")
     *
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $animals;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     *
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $createDate;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     *
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $startDate;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     *
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $endDate;

    /**
     * @var Client
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Client")
     *
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $owner;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="creation_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Person")
     *
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $creationBy;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="edited_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Person")
     *
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $editedBy;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="deleted_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Person")
     *
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $deletedBy;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     *
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $description;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":true}, nullable=false)
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     *
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $isActive;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Groups({
     *     "TEMPLATE",
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $status = RequestStateType::COMPLETED;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(name="revoked_by_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\Person")
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $revokedBy;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $revokeDate;

    /**
     * @var TreatmentTemplate
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\TreatmentTemplate", inversedBy="treatments")
     * @JMS\Type("AppBundle\Entity\TreatmentTemplate")
     *
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $treatmentTemplate;

    /**
     * @var ArrayCollection<MedicationSelection>
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\MedicationSelection", mappedBy="treatment", cascade={"persist", "remove"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\MedicationSelection>")
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $medicationSelections;

    /**
     * @var ArrayCollection|DeclareAnimalFlag[]
     * @ORM\OneToMany(targetEntity="DeclareAnimalFlag", mappedBy="treatment", cascade={"persist"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\DeclareAnimalFlag>")
     * @JMS\Groups({
     *     "TREATMENT"
     * })
     */
    private $declareAnimalFlags;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("dutchType")
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    public function getDutchType() {
        return Translation::getDutchTreatmentType($this->type);
    }

    /**
     * Treatment constructor.
     */
    public function __construct()
    {
        $this->createDate = new DateTime();
        $this->isActive = true;
        $this->animals = new ArrayCollection();
        $this->medicationSelections = new ArrayCollection();
        $this->declareAnimalFlags = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Treatment
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return ArrayCollection|Animal[]
     */
    public function getAnimals()
    {
        return $this->animals;
    }

    /**
     * @param ArrayCollection $animals
     * @return Treatment
     */
    public function setAnimals($animals): self
    {
        $this->animals = $animals;
        return $this;
    }

    /**
     * @param Animal $animal
     * @return Treatment
     */
    public function addAnimal($animal)
    {
        $this->animals->add($animal);
        return $this;
    }

    /**
     * @param Animal $animal
     * @return Treatment
     */
    public function removeAnimal($animal)
    {
        $this->animals->removeElement($animal);
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * @param DateTime $createDate
     * @return Treatment
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     * @return Treatment
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param DateTime $endDate
     * @return Treatment
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return Client
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Client $owner
     * @return Treatment
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return Person
     */
    public function getCreationBy()
    {
        return $this->creationBy;
    }

    /**
     * @param Person $creationBy
     * @return Treatment
     */
    public function setCreationBy($creationBy)
    {
        $this->creationBy = $creationBy;
        return $this;
    }

    /**
     * @return Person
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * @param Person $editedBy
     * @return Treatment
     */
    public function setEditedBy($editedBy)
    {
        $this->editedBy = $editedBy;
        return $this;
    }

    /**
     * @return Person
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * @param Person $deletedBy
     * @return Treatment
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Treatment
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return Treatment
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Treatment
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Treatment
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return Person
     */
    public function getRevokedBy(): Person
    {
        return $this->revokedBy;
    }

    /**
     * @param Person $revokedBy
     * @return Treatment
     */
    public function setRevokedBy(Person $revokedBy): self
    {
        $this->revokedBy = $revokedBy;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getRevokeDate(): DateTime
    {
        return $this->revokeDate;
    }

    /**
     * @param DateTime $revokeDate
     * @return Treatment
     */
    public function setRevokeDate(DateTime $revokeDate): self
    {
        $this->revokeDate = $revokeDate;
        return $this;
    }

    /**
     * @return Client|null
     */
    public function getLocationOwner()
    {
        if ($this->location) {
            return $this->location->getOwner();
        }
        return null;
    }

    /**
     * @return ArrayCollection<MedicationSelection>
     */
    public function getMedicationSelections()
    {
        return $this->medicationSelections;
    }

    /**
     * @param ArrayCollection $medicationSelections
     * @return Treatment
     */
    public function setMedicationSelections(ArrayCollection $medicationSelections): self
    {
        $this->medicationSelections = $medicationSelections;
        return $this;
    }

    /**
     * @param MedicationSelection $medicationSelection
     * @return Treatment
     */
    public function addMedicationSelection(MedicationSelection $medicationSelection): self
    {
        $this->medicationSelections->add($medicationSelection);
        return $this;
    }

    /**
     * @param MedicationSelection $medicationSelection
     * @return Treatment
     */
    public function removeMedicationSelection(MedicationSelection $medicationSelection): self
    {
        $this->medicationSelections->removeElement($medicationSelection);
        return $this;
    }

    /**
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * @param Location $location
     * @return Treatment
     */
    public function setLocation(Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return TreatmentTemplate
     */
    public function getTreatmentTemplate(): TreatmentTemplate
    {
        return $this->treatmentTemplate;
    }

    /**
     * @param TreatmentTemplate $treatmentTemplate
     * @return Treatment
     */
    public function setTreatmentTemplate(TreatmentTemplate $treatmentTemplate): self
    {
        $this->treatmentTemplate = $treatmentTemplate;
        return $this;
    }

    /**
     * Add DeclareAnimalFlag
     *
     * @param DeclareAnimalFlag $flag
     *
     * @return Treatment
     */
    public function addDeclareAnimalFlag(DeclareAnimalFlag $flag): Treatment
    {
        $this->declareAnimalFlags->add($flag);
        return $this;
    }

    /**
     * Remove DeclareAnimalFlag
     *
     * @param DeclareAnimalFlag $flag
     * @return Treatment
     */
    public function removeDeclareAnimalFlag(DeclareAnimalFlag $flag): Treatment
    {
        $this->declareAnimalFlags->removeElement($flag);
        return $this;
    }

    /**
     * Get DeclareAnimalFlags
     *
     * @return Collection|ArrayCollection|DeclareAnimalFlag[]
     */
    public function getDeclareAnimalFlags()
    {
        return $this->declareAnimalFlags;
    }
}
