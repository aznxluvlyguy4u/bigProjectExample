<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class PedigreeRegisterRegistration
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PedigreeRegisterRegistrationRepository")
 * @package AppBundle\Entity
 */
class PedigreeRegisterRegistration
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     * })
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     * })
     */
    private $breederNumber;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="pedigreeRegisterRegistrations")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Location>")
     * @JMS\Groups({
     * })
     */
    private $location;

    /**
     * @var PedigreeRegister
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PedigreeRegister")
     * @ORM\JoinColumn(name="pedigree_register_id", referencedColumnName="id")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\PedigreeRegister>")
     * @JMS\Groups({
     * })
     */
    private $pedigreeRegister;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     * })
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     * })
     */
    private $endDate;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="created_by_id", referencedColumnName="id")
     * @JMS\Groups({
     * })
     */
    private $createdBy;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="deleted_by_id", referencedColumnName="id")
     * @JMS\Groups({
     * })
     */
    private $deletedBy;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     * })
     */
    protected $isActive;

    public function __construct()
    {
        $this->setIsActive(true);
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
     * @return PedigreeRegisterRegistration
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getBreederNumber()
    {
        return $this->breederNumber;
    }

    /**
     * @param string $breederNumber
     * @return PedigreeRegisterRegistration
     */
    public function setBreederNumber($breederNumber)
    {
        $this->breederNumber = $breederNumber;
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
     * @return PedigreeRegisterRegistration
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return PedigreeRegister
     */
    public function getPedigreeRegister()
    {
        return $this->pedigreeRegister;
    }

    /**
     * @param PedigreeRegister $pedigreeRegister
     * @return PedigreeRegisterRegistration
     */
    public function setPedigreeRegister($pedigreeRegister)
    {
        $this->pedigreeRegister = $pedigreeRegister;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return PedigreeRegisterRegistration
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     * @return PedigreeRegisterRegistration
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return Person
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param Person $createdBy
     * @return PedigreeRegisterRegistration
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
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
     * @return PedigreeRegisterRegistration
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
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
     * @return PedigreeRegisterRegistration
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }


}