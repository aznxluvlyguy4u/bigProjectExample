<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Mate
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MateRepository")
 * @package AppBundle\Entity
 */
class Mate extends DeclareNsfoBase {

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $startDate;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $endDate;

    /**
     * @var Ram
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy = "matings", cascade={"persist"})
     * @ORM\JoinColumn(name="stud_ram_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Ram")
     */
    private $studRam;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ramUlnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ramUlnNumber;
    
    
    /**
     * @var Ewe
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy = "matings", cascade={"persist"})
     * @ORM\JoinColumn(name="stud_ewe_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Ewe")
     */
    private $studEwe;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $pmsg;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $ki;


    /**
     * Equal to matching litterOrdinal.
     * For new mates find the max(litterOrdinal) of current litter. Then add 1.
     *
     * @var integer
     * @ORM\Column(type="integer", nullable=true, options={"default":null})
     * @JMS\Type("integer")
     */
    private $parity;

    /**
     * The number designating then place in an ordered sequence of mates for a specific ewe
     * starting at 1 and grouped by parity.
     * So restart the sequence when parity number is incremented.
     * 
     * @var integer
     * @ORM\Column(type="integer", nullable=true, options={"default":null})
     * @JMS\Type("integer")
     */
    private $mateOrdinalAfterParity;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true, options={"default":null})
     * @JMS\Type("boolean")
     */
    private $isApprovedByThirdParty;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $approvalDate;

    /**
     * The thirdParty approving or rejecting the Mate.
     *
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="approved_by_id", referencedColumnName="id")
     */
    protected $approvedBy;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Mate", mappedBy="currentVersion", cascade={"persist"})
     */
    private $previousVersions;

    /**
     * @var Mate
     * @ORM\ManyToOne(targetEntity="Mate", inversedBy="previousVersions", cascade={"persist"})
     * @ORM\JoinColumn(name="current_version_id", referencedColumnName="id")
     */
    private $currentVersion;
    
    /**
     * @var Location
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="matings", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    
    public function __construct() {
      parent::__construct();
      $this->previousVersions = new ArrayCollection();
      $this->isApprovedByThirdParty = null;
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
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return Mate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Mate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Mate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set studMale
     *
     * @param \AppBundle\Entity\Animal $studRam
     *
     * @return Mate
     */
    public function setStudRam(\AppBundle\Entity\Animal $studRam = null)
    {
        $this->studRam = $studRam;

        return $this;
    }

    /**
     * Get studMale
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getStudRam()
    {
        return $this->studRam;
    }

    /**
     * @return string
     */
    public function getRamUlnCountryCode()
    {
        return $this->ramUlnCountryCode;
    }

    /**
     * @param string $ramUlnCountryCode
     */
    public function setRamUlnCountryCode($ramUlnCountryCode)
    {
        $this->ramUlnCountryCode = $ramUlnCountryCode;
    }

    /**
     * @return string
     */
    public function getRamUlnNumber()
    {
        return $this->ramUlnNumber;
    }

    /**
     * @param string $ramUlnNumber
     */
    public function setRamUlnNumber($ramUlnNumber)
    {
        $this->ramUlnNumber = $ramUlnNumber;
    }
    
    /**
     * @return Ewe
     */
    public function getStudEwe()
    {
        return $this->studEwe;
    }

    /**
     * @param Ewe $studEwe
     */
    public function setStudEwe($studEwe)
    {
        $this->studEwe = $studEwe;
    }

    /**
     * @return boolean|null
     */
    public function getPmsg()
    {
        return $this->pmsg;
    }

    /**
     * @param boolean|null $pmsg
     */
    public function setPmsg($pmsg)
    {
        $this->pmsg = $pmsg;
    }

    /**
     * @return boolean
     */
    public function getKi()
    {
        return $this->ki;
    }

    /**
     * @param boolean $ki
     */
    public function setKi($ki)
    {
        $this->ki = $ki;
    }

    /**
     * @return integer
     */
    public function getParity()
    {
        return $this->parity;
    }

    /**
     * @param integer $parity
     */
    public function setParity($parity)
    {
        $this->parity = $parity;
    }

    /**
     * @return integer
     */
    public function getMateOrdinalAfterParity()
    {
        return $this->mateOrdinalAfterParity;
    }

    /**
     * @param integer $mateOrdinalAfterParity
     */
    public function setMateOrdinalAfterParity($mateOrdinalAfterParity)
    {
        $this->mateOrdinalAfterParity = $mateOrdinalAfterParity;
    }
    
    /**
     * @return boolean
     */
    public function getIsApprovedByThirdParty()
    {
        return $this->isApprovedByThirdParty;
    }

    /**
     * @param boolean $isApprovedByThirdParty
     */
    public function setIsApprovedByThirdParty($isApprovedByThirdParty)
    {
        $this->isApprovedByThirdParty = $isApprovedByThirdParty;
    }

    /**
     * @return Mate
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * @param Mate $currentVersion
     */
    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;
    }

    /**
     * @return ArrayCollection
     */
    public function getPreviousVersions()
    {
        return $this->previousVersions;
    }

    /**
     * @param ArrayCollection $previousVersions
     */
    public function setPreviousVersions($previousVersions)
    {
        $this->previousVersions = $previousVersions;
    }


    /**
     * Add a previousVersion Mate
     *
     * @param Mate $previousVersion
     *
     * @return Mate
     */
    public function addPreviousVersion(Mate $previousVersion)
    {
        $this->previousVersions[] = $previousVersion;
    }

    /**
     * Remove a previousVersion Mate
     *
     * @param Mate $previousVersion
     */
    public function removePreviousVersion(Mate $previousVersion)
    {
        $this->previousVersions->removeElement($previousVersion);
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
     * @return null|\DateTime
     */
    public function getApprovalDate()
    {
        return $this->approvalDate;
    }

    /**
     * @param \DateTime $approvalDate
     */
    public function setApprovalDate($approvalDate)
    {
        $this->approvalDate = $approvalDate;
    }

    /**
     * @return Person
     */
    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    /**
     * @param Person $approvedBy
     */
    public function setApprovedBy($approvedBy)
    {
        $this->approvedBy = $approvedBy;
    }


    /**
     * @param Mate $declareWeight
     */
    public function duplicateValues(Mate $declareWeight)
    {
        //Note 'currentVersion' and 'previousVersions' are not duplicated. They set the history relationship.
        //The OneToMany reference is used to group them.
        parent::duplicateBaseValues($declareWeight);
        
        //Mate specific values
        $this->setStartDate($declareWeight->getStartDate());
        $this->setEndDate($declareWeight->getEndDate());
        $this->setStudRam($declareWeight->getStudRam());
        $this->setStudEwe($declareWeight->getStudEwe());
        $this->setRamUlnCountryCode($declareWeight->getRamUlnCountryCode());
        $this->setRamUlnNumber($declareWeight->getRamUlnNumber());
        $this->setPmsg($declareWeight->getPmsg());
        $this->setKi($declareWeight->getKi());
        $this->setIsApprovedByThirdParty($declareWeight->getIsApprovedByThirdParty());
        $this->setApprovalDate($declareWeight->getApprovalDate());
        $this->setApprovedBy($declareWeight->getApprovedBy());
        $this->setLocation($declareWeight->getLocation());
    }
}
