<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AnimalResidence
 * @ORM\Table(name="animal_residence",indexes={@ORM\Index(name="residence_idx", columns={"location_id", "animal_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalResidenceRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class AnimalResidence
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "BASIC",
     *     "EDIT_OVERVIEW"
     * })
     * @Expose
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "BASIC",
     *     "EDIT_OVERVIEW"
     * })
     * @Expose
     */
    private $logDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "BASIC",
     *     "EDIT_OVERVIEW"
     * })
     * @Expose
     */
    private $startDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "BASIC",
     *     "EDIT_OVERVIEW"
     * })
     * @Expose
     */
    private $endDate;

    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="animalResidenceHistory")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @JMS\Groups({
     *     "EDIT_OVERVIEW"
     * })
     * @Expose
     */
    private $animal;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="animalResidenceHistory")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({
     *     "BASIC",
     *     "EDIT_OVERVIEW"
     * })
     * @Expose
     */
    private $location;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "BASIC",
     *     "EDIT_OVERVIEW"
     * })
     * @Expose
     */
    private $isPending;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC",
     *     "EDIT_OVERVIEW"
     * })
     * @Expose
     */
    private $country;

    /**
     * @var EditType
     * @ORM\ManyToOne(targetEntity="EditType")
     * @ORM\JoinColumn(name="start_date_edit_type", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\EditType")
     * @JMS\SerializedName("start_date_edit_type_object")
     * @Expose
     */
    private $startDateEditType;

    /**
     * @var EditType
     * @ORM\ManyToOne(targetEntity="EditType")
     * @ORM\JoinColumn(name="end_date_edit_type", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\EditType")
     * @JMS\SerializedName("end_date_edit_type_object")
     * @Expose
     */
    private $endDateEditType;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="start_date_edited_by", referencedColumnName="id")
     */
    private $startDateEditedBy;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="end_date_edited_by", referencedColumnName="id")
     */
    private $endDateEditedBy;


    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("start_date_edit_type")
     * @JMS\Groups({
     *     "EDIT_OVERVIEW"
     * })
     * @return null|string
     */
    public function getStartDateEditTypeName(): ?string
    {
        return $this->startDateEditType ? $this->startDateEditType->getName() : null;
    }


    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("end_date_edit_type")
     * @JMS\Groups({
     *     "EDIT_OVERVIEW"
     * })
     * @return null|string
     */
    public function getEndDateEditTypeName(): ?string
    {
        return $this->endDateEditType ? $this->endDateEditType->getName() : null;
    }


    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("start_date_edited_by_full_name")
     * @JMS\Groups({
     *     "EDIT_OVERVIEW"
     * })
     * @return null|string
     */
    public function getStartDateEditedByFullName(): ?string
    {
        return $this->startDateEditedBy ? $this->startDateEditedBy->getFullName() : null;
    }


    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("end_date_edited_by_full_name")
     * @JMS\Groups({
     *     "EDIT_OVERVIEW"
     * })
     * @return null|string
     */
    public function getEndDateEditedByFullName(): ?string
    {
        return $this->endDateEditedBy ? $this->endDateEditedBy->getFullName() : null;
    }


    public function __construct($countryCode, $isPending = true)
    {
        $this->isPending = $isPending;
        $this->country = $countryCode;
        $this->logDate = new \DateTime('now');
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
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
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param Animal $animal
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
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
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return boolean
     */
    public function isPending()
    {
        return $this->isPending;
    }

    /**
     * @param boolean $isPending
     */
    public function setIsPending($isPending)
    {
        $this->isPending = $isPending;
    }



    /**
     * Get isPending
     *
     * @return boolean
     */
    public function getIsPending()
    {
        return $this->isPending;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
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
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    }

    /**
     * @return EditType
     */
    public function getStartDateEditType()
    {
        return $this->startDateEditType;
    }

    /**
     * @param EditType $startDateEditType
     */
    public function setStartDateEditType($startDateEditType)
    {
        $this->startDateEditType = $startDateEditType;
    }

    /**
     * @return EditType
     */
    public function getEndDateEditType()
    {
        return $this->endDateEditType;
    }

    /**
     * @param EditType $endDateEditType
     */
    public function setEndDateEditType($endDateEditType)
    {
        $this->endDateEditType = $endDateEditType;
    }

    /**
     * @return Person
     */
    public function getStartDateEditedBy()
    {
        return $this->startDateEditedBy;
    }

    /**
     * @param Person $startDateEditedBy
     */
    public function setStartDateEditedBy($startDateEditedBy)
    {
        $this->startDateEditedBy = $startDateEditedBy;
    }

    /**
     * @return Person
     */
    public function getEndDateEditedBy()
    {
        return $this->endDateEditedBy;
    }

    /**
     * @param Person $endDateEditedBy
     */
    public function setEndDateEditedBy($endDateEditedBy)
    {
        $this->endDateEditedBy = $endDateEditedBy;
    }


    /**
     * @return int|null
     */
    public function getAnimalId(): ?int
    {
        return $this->animal ? $this->animal->getId() : null;
    }


    /**
     * @return string|null
     */
    public function getLocationApiKeyId(): ?string
    {
        return $this->location ? $this->location->getLocationId() : null;
    }


    /**
     * @return null|string
     */
    public function getUbn(): ?string
    {
        return $this->location ? $this->location->getUbn() : null;
    }
}
