<?php

namespace AppBundle\Entity;


use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
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
     */
    private $id;

    /**
     * @var ArrayCollection|Animal[]
     * @ORM\ManyToMany(targetEntity="Animal", inversedBy="treatments", cascade={"remove"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Animal>")
     */
    private $animals;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $createDate;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $treatmentStartDate;

    /**
     * @var DateTime
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
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":true}, nullable=false)
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $isActive;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank
     */
    private $type;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Location")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=false)
     * @JMS\Type("AppBundle\Entity\Location")
     * @Assert\NotBlank
     */
    private $location;

    /**
     * @var TreatmentTemplate
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\TreatmentTemplate")
     * @JMS\Type("AppBundle\Entity\TreatmentTemplate")
     */
    private $treatmentTemplate;

    /**
     * Treatment constructor.
     */
    public function __construct()
    {
        $this->createDate = new DateTime();
        $this->isActive = true;
        $this->animals = new ArrayCollection();
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
    public function getTreatmentStartDate()
    {
        return $this->treatmentStartDate;
    }

    /**
     * @param DateTime $treatmentStartDate
     * @return Treatment
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
     * @return Treatment
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
     * @return Treatment
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
     * @return Treatment
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
     * @return Treatment
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


    
}