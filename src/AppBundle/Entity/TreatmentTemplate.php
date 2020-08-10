<?php

namespace AppBundle\Entity;


use AppBundle\Enumerator\AnimalType;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\Translation;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class TreatmentTemplate
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TreatmentTemplateRepository")
 * @package AppBundle\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="templatetype", type="string")
 * @ORM\DiscriminatorMap({"QFever"="QFever", "Default"="DefaultTreatmentTemplate"})
 * @JMS\Discriminator(field="templatetype", disabled=false, map={
 *                        "QFever" : "AppBundle\Entity\QFever",
 *                        "Default" : "AppBundle\Entity\DefaultTreatmentTemplate"
 *                      },
 *                      groups = {
 *                          "TREATMENT_TEMPLATE",
 *                          "TREATMENT"
 *                      }
 *                  )
 */
abstract class TreatmentTemplate
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT",
     *     "TREATMEN_MIN"
     * })
     */
    private $id;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="treatmentTemplates")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN",
     *     "TREATMENT",
     *     "TREATMEN_MIN"
     * })
     */
    private $location;

    /**
     * @var TreatmentType
     * @ORM\ManyToOne(targetEntity="TreatmentType")
     * @JMS\Type("AppBundle\Entity\TreatmentType")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE"
     * })
     */
    private $treatmentType;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string", unique=true)
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     */
    private $description;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\TreatmentMedication", inversedBy="treatmentTemplates", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="template_medications")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\TreatmentMedication>")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN",
     *     "TREATMENT"
     * })
     * @JMS\MaxDepth(depth=2)
     */
    private $treatmentMedications;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Treatment", mappedBy="treatmentTemplate", cascade={"persist"})
     */
    private $treatments;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT",
     *     "TREATMENT_TEMPLATE_MIN",
     *     "TREATMEN_MIN"
     * })
     */
    private $isActive;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     */
    private $isEditable;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE"
     * })
     */
    private $logDate;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="Employee")
     * @ORM\JoinColumn(name="creation_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Employee")
     */
    private $creationBy;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="Employee")
     * @ORM\JoinColumn(name="edited_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Employee")
     */
    private $editedBy;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="Employee")
     * @ORM\JoinColumn(name="deleted_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Employee")
     */
    private $deletedBy;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE"
     * })
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false, options={"default":AppBundle\Enumerator\AnimalType::sheep})
     * @JMS\Type("integer")
     * @Assert\NotNull
     */
    private $animalType;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("dutchType")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE"
     * })
     */
    public function getDutchType() {
        return Translation::getDutchTreatmentType($this->type);
    }

    /**
     * TreatmentTemplate constructor.
     */
    public function __construct()
    {
        $this->logDate = new \DateTime();
        $this->isActive = true;
        $this->isEditable = true;
        $this->setAnimalType(AnimalType::sheep);
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
     * @return TreatmentTemplate
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     * @return TreatmentTemplate
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return TreatmentType
     */
    public function getTreatmentType()
    {
        return $this->treatmentType;
    }

    /**
     * @param TreatmentType $treatmentType
     * @return TreatmentTemplate
     */
    public function setTreatmentType($treatmentType)
    {
        $this->treatmentType = $treatmentType;
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
     * @return TreatmentTemplate
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return ArrayCollection
     *
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     */
    public function getMedications()
    {
        return $this->treatmentMedications;
    }

    /**
     * @param ArrayCollection $medications
     * @return TreatmentTemplate
     */
    public function setMedications($medications)
    {
        $this->treatmentMedications = $medications;
        return $this;
    }

    /**
     * @param TreatmentMedication $TreatmentMedication
     * @return TreatmentTemplate
     */
    public function addMedication(TreatmentMedication $TreatmentMedication)
    {
        $this->treatmentMedications->add($TreatmentMedication);
        return $this;
    }

    /**
     * @param TreatmentMedication $TreatmentMedication
     * @return TreatmentTemplate
     */
    public function removeMedication(TreatmentMedication $TreatmentMedication)
    {
        $this->treatmentMedications->removeElement($TreatmentMedication);
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
     * @return TreatmentTemplate
     */
    public function setTreatments($treatments)
    {
        $this->treatments = $treatments;
        return $this;
    }

    /**
     * @param Treatment $treatment
     * @return TreatmentTemplate
     */
    public function addTreatment(Treatment $treatment)
    {
        $this->treatments->add($treatment);
        return $this;
    }

    /**
     * @param Treatment $treatment
     * @return TreatmentTemplate
     */
    public function removeTreatment(Treatment $treatment)
    {
        $this->treatments->removeElement($treatment);
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
     * @return TreatmentTemplate
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param DateTime $logDate
     * @return TreatmentTemplate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return Employee
     */
    public function getCreationBy()
    {
        return $this->creationBy;
    }

    /**
     * @param Employee $creationBy
     * @return TreatmentTemplate
     */
    public function setCreationBy($creationBy)
    {
        $this->creationBy = $creationBy;
        return $this;
    }

    /**
     * @return Employee
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * @param Employee $editedBy
     * @return TreatmentTemplate
     */
    public function setEditedBy($editedBy)
    {
        $this->editedBy = $editedBy;
        return $this;
    }

    /**
     * @return Employee
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * @param Employee $deletedBy
     * @return TreatmentTemplate
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return TreatmentTemplate
     */
    public function setType($type)
    {
        $this->type = $type;
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
     * @param string $nullReplacement
     * @return null|string
     */
    public function getUbn($nullReplacement = null)
    {
        if ($this->location) {
            return $this->location->getUbn();
        }
        return $nullReplacement;
    }

    /**
     * @return int
     */
    public function getAnimalType(): int
    {
        return $this->animalType;
    }

    /**
     * @param  int  $animalType
     * @return TreatmentTemplate
     */
    public function setAnimalType(int $animalType): TreatmentTemplate
    {
        $this->animalType = $animalType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this->isEditable;
    }

    /**
     * @param  bool  $isEditable
     * @return TreatmentTemplate
     */
    public function setIsEditable(bool $isEditable): TreatmentTemplate
    {
        $this->isEditable = $isEditable;
        return $this;
    }


}
