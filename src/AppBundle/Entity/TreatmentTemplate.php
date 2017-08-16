<?php

namespace AppBundle\Entity;


use AppBundle\Entity\Employee;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;


/**
 * Class TreatmentTemplate
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TreatmentTemplateRepository")
 * @package AppBundle\Entity
 */
class TreatmentTemplate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({"TREATMENT_TEMPLATE","TREATMENT_TEMPLATE_MIN"})
     */
    private $id;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="treatmentTemplates")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({"TREATMENT_TEMPLATE","TREATMENT_TEMPLATE_MIN"})
     */
    private $location;

    /**
     * @var TreatmentType
     * @ORM\ManyToOne(targetEntity="TreatmentType")
     * @JMS\Type("AppBundle\Entity\TreatmentType")
     * @JMS\Groups({"TREATMENT_TEMPLATE"})
     */
    private $treatmentType;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string", unique=true)
     * @JMS\Groups({"TREATMENT_TEMPLATE","TREATMENT_TEMPLATE_MIN"})
     */
    private $description;

    /**
     * @var ArrayCollection
     * @ORM\OrderBy({"description" = "ASC"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\MedicationOption", mappedBy="treatmentTemplate", cascade={"persist", "remove"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\MedicationOption>")
     * @JMS\Groups({"TREATMENT_TEMPLATE","TREATMENT_TEMPLATE_MIN"})
     */
    private $medications;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({"TREATMENT_TEMPLATE","TREATMENT_TEMPLATE_MIN"})
     */
    private $isActive;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({"TREATMENT_TEMPLATE"})
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
     * @JMS\Groups({"TREATMENT_TEMPLATE","TREATMENT_TEMPLATE_MIN"})
     */
    private $type;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("dutchType")
     * @JMS\Groups({"TREATMENT_TEMPLATE","TREATMENT_TEMPLATE_MIN"})
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
     */
    public function getMedications()
    {
        return $this->medications;
    }

    /**
     * @param ArrayCollection $medications
     * @return TreatmentTemplate
     */
    public function setMedications($medications)
    {
        $this->medications = $medications;
        return $this;
    }

    /**
     * @param MedicationOption $medicationOption
     * @return $this
     */
    public function addMedication(MedicationOption $medicationOption)
    {
        $this->medications->add($medicationOption);
        return $this;
    }

    /**
     * @param MedicationOption $medicationOption
     * @return $this
     */
    public function removeMedication(MedicationOption $medicationOption)
    {
        $this->medications->removeElement($medicationOption);
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

    
}