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
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy = "matings")
     * @ORM\JoinColumn(name="animal_father_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Ram")
     */
    private $studRam;

    /**
     * @var Ewe
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy = "matings")
     * @ORM\JoinColumn(name="animal_mother_id", referencedColumnName="id")
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
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true, options={"default":null})
     * @JMS\Type("boolean")
     */
    private $isAcceptedByThirdParty;

    /**
     * @var Mate
     * @ORM\OneToMany(targetEntity="Mate", mappedBy="previousVersions")
     */
    private $currentVersion;

    /**
     * @var ArrayCollection
     * @ORM\ManyToOne(targetEntity="Mate", inversedBy="currentVersion")
     * @ORM\JoinColumn(name="previous_versions_id", referencedColumnName="id")
     */
    private $previousVersions;


    public function __construct() {
      parent::__construct();
      $this->previousVersions = new ArrayCollection();
      $this->isAcceptedByThirdParty = null;
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
    public function isKi()
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
     * @return boolean
     */
    public function isIsAcceptedByThirdParty()
    {
        return $this->isAcceptedByThirdParty;
    }

    /**
     * @param boolean $isAcceptedByThirdParty
     */
    public function setIsAcceptedByThirdParty($isAcceptedByThirdParty)
    {
        $this->isAcceptedByThirdParty = $isAcceptedByThirdParty;
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





}
