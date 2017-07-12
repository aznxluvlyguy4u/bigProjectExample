<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Location;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\Country as CountryEnumerator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class AnimalResidence
 * @ORM\Table(name="animal_residence",indexes={@ORM\Index(name="residence_idx", columns={"location_id", "animal_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalResidenceRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class AnimalResidence
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
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
     * @Expose
     */
    private $logDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $startDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $endDate;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="animalResidenceHistory")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    private $animal;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="animalResidenceHistory")
     * @JMS\Type("AppBundle\Entity\Location")
     * @Expose
     */
    private $location;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Expose
     */
    private $isPending;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @Expose
     */
    private $country;

    /**
     * @var EditType
     * @ORM\ManyToOne(targetEntity="EditType")
     * @ORM\JoinColumn(name="start_date_edit_type", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\EditType")
     * @Expose
     */
    private $startDateEditType;

    /**
     * @var EditType
     * @ORM\ManyToOne(targetEntity="EditType")
     * @ORM\JoinColumn(name="end_date_edit_type", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\EditType")
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
     * AnimalResidence constructor.
     */
    public function __construct($country = CountryEnumerator::NL, $isPending = true)
    {
        $this->isPending = $isPending;
        $this->country = $country;
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
    public function isIsPending()
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



}
