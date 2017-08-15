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
 * Class TreatmentAnimal
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TreatmentAnimalRepository")
 * @package AppBundle\Entity
 */
class TreatmentAnimal implements TreatmentInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="treatments")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $treatmentStartDate;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $treatmentEndDate;

    /**
     * @var Client
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Client")
     */
    private $owner;

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
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $dosage;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":true}, nullable=false)
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $isActive;

    /**
     * TreatmentAnimal constructor.
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
     * @return TreatmentAnimal
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
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
     * @return TreatmentAnimal
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
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
     * @return TreatmentAnimal
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getTreatmentStartDate()
    {
        return $this->treatmentStartDate;
    }

    /**
     * @param DateTime $treatmentStartDate
     * @return TreatmentAnimal
     */
    public function setTreatmentStartDate($treatmentStartDate)
    {
        $this->treatmentStartDate = $treatmentStartDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getTreatmentEndDate()
    {
        return $this->treatmentEndDate;
    }

    /**
     * @param DateTime $treatmentEndDate
     * @return TreatmentAnimal
     */
    public function setTreatmentEndDate($treatmentEndDate)
    {
        $this->treatmentEndDate = $treatmentEndDate;
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
     * @return TreatmentAnimal
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
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
     * @return TreatmentAnimal
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
     * @return TreatmentAnimal
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
     * @return TreatmentAnimal
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
     * @return TreatmentAnimal
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return float
     */
    public function getDosage()
    {
        return $this->dosage;
    }

    /**
     * @param float $dosage
     * @return TreatmentAnimal
     */
    public function setDosage($dosage)
    {
        $this->dosage = $dosage;
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
     * @return TreatmentAnimal
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }


    
}