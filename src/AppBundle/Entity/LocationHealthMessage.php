<?php

namespace AppBundle\Entity;


use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use \DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class LocationHealthMessage
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationHealthMessageRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class LocationHealthMessage
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    private $id;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="healthMessages")
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @Expose
     */
    private $checkForMaediVisna;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @Expose
     */
    private $checkForScrapie;

    /**
     * Reason here means 'arrival' or 'import'
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $reasonOfHealthStatusDemotion;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @Expose
     */
    private $ubnNewOwner;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @Expose
     */
    private $ubnPreviousOwner;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $animalCountryOrigin;

    /**
     * @var DeclareArrival
     *
     * @ORM\OneToOne(targetEntity="DeclareArrival", mappedBy="healthMessage")
     */
    private $arrival;

    /**
     * @var DeclareImport
     *
     * @ORM\OneToOne(targetEntity="DeclareImport", mappedBy="healthMessage")
     */
    private $import;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $originScrapieStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $originMaediVisnaStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $originCaseousLymphadenitisStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $originFootRotStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $destinationScrapieStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $destinationMaediVisnaStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $destinationCaseousLymphadenitisStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $destinationFootRotStatus;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $arrivalDate;

    /**
     * @ORM\ManyToOne(targetEntity="MaediVisna")
     * @ORM\JoinColumn(name="maedi_visna_id", referencedColumnName="id")
     */
    private $maediVisna;

    /**
     * @ORM\ManyToOne(targetEntity="Scrapie")
     * @ORM\JoinColumn(name="scrapie_id", referencedColumnName="id")
     */
    private $scrapie;

    /**
     * @ORM\ManyToOne(targetEntity="CaseousLymphadenitis")
     * @ORM\JoinColumn(name="caseous_lymphadenitis_id", referencedColumnName="id")
     */
    private $caseousLymphadenitis;

    /**
     * @ORM\ManyToOne(targetEntity="FootRot")
     * @ORM\JoinColumn(name="foot_rot_id", referencedColumnName="id")
     */
    private $footRot;

    /**
     * LocationHealthMessage constructor.
     */
    public function __construct()
    {
        $this->setLogDate(new \DateTime('now'));
    }


    /**
     * @return null|string
     */
    public function getRequestState()
    {
        if($this->arrival != null) {
            return $this->arrival->getRequestState();
        } else if($this->import != null) {
            return $this->import->getRequestState();
        } else {
            return null;
        }
    }

    /**
     * @return null|string
     */
    public function getRequestId()
    {
        if($this->arrival != null) {
            return $this->arrival->getRequestId();
        } else if($this->import != null) {
            return $this->import->getRequestId();
        } else {
            return null;
        }
    }

    /**
     * @return DeclareArrival|DeclareImport|null
     */
    public function getRequest()
    {
        if($this->arrival != null) {
            return $this->arrival;
        } else if($this->import != null) {
            return $this->import;
        } else {
            return null;
        }
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
     * @return LocationHealthMessage
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
     * @return boolean
     */
    public function isCheckForMaediVisna()
    {
        return $this->checkForMaediVisna;
    }

    /**
     * @param boolean $checkForMaediVisna
     */
    public function setCheckForMaediVisna($checkForMaediVisna)
    {
        $this->checkForMaediVisna = $checkForMaediVisna;
    }

    /**
     * @return boolean
     */
    public function isCheckForScrapie()
    {
        return $this->checkForScrapie;
    }

    /**
     * @param boolean $checkForScrapie
     */
    public function setCheckForScrapie($checkForScrapie)
    {
        $this->checkForScrapie = $checkForScrapie;
    }

    /**
     * Set reasonOfHealthStatusDemotion
     *
     * @param string $reasonOfHealthStatusDemotion
     *
     * @return LocationHealthMessage
     */
    public function setReasonOfHealthStatusDemotion($reasonOfHealthStatusDemotion)
    {
        $this->reasonOfHealthStatusDemotion = $reasonOfHealthStatusDemotion;

        return $this;
    }

    /**
     * Get reasonOfHealthStatusDemotion
     *
     * @return string
     */
    public function getReasonOfHealthStatusDemotion()
    {
        return $this->reasonOfHealthStatusDemotion;
    }

    /**
     * Set ubnNewOwner
     *
     * @param string $ubnNewOwner
     *
     * @return LocationHealthMessage
     */
    public function setUbnNewOwner($ubnNewOwner)
    {
        $this->ubnNewOwner = $ubnNewOwner;

        return $this;
    }

    /**
     * Get ubnNewOwner
     *
     * @return string
     */
    public function getUbnNewOwner()
    {
        return $this->ubnNewOwner;
    }

    /**
     * Set ubnPreviousOwner
     *
     * @param string $ubnPreviousOwner
     *
     * @return LocationHealthMessage
     */
    public function setUbnPreviousOwner($ubnPreviousOwner)
    {
        $this->ubnPreviousOwner = $ubnPreviousOwner;

        return $this;
    }

    /**
     * Get ubnPreviousOwner
     *
     * @return string
     */
    public function getUbnPreviousOwner()
    {
        return $this->ubnPreviousOwner;
    }

    /**
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return LocationHealthMessage
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return LocationHealthMessage
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Get ulnCountryCode
     *
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * @return string
     */
    public function getAnimalCountryOrigin()
    {
        return $this->animalCountryOrigin;
    }

    /**
     * @param string $animalCountryOrigin
     */
    public function setAnimalCountryOrigin($animalCountryOrigin)
    {
        $this->animalCountryOrigin = $animalCountryOrigin;
    }

    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return LocationHealthMessage
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set arrival
     *
     * @param \AppBundle\Entity\DeclareArrival $arrival
     *
     * @return LocationHealthMessage
     */
    public function setArrival(\AppBundle\Entity\DeclareArrival $arrival = null)
    {
        $this->arrival = $arrival;

        return $this;
    }

    /**
     * Get arrival
     *
     * @return \AppBundle\Entity\DeclareArrival
     */
    public function getArrival()
    {
        return $this->arrival;
    }

    /**
     * Set import
     *
     * @param \AppBundle\Entity\DeclareImport $import
     *
     * @return LocationHealthMessage
     */
    public function setImport(\AppBundle\Entity\DeclareImport $import = null)
    {
        $this->import = $import;

        return $this;
    }

    /**
     * Get import
     *
     * @return \AppBundle\Entity\DeclareImport
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * Get checkForMaediVisna
     *
     * @return boolean
     */
    public function getCheckForMaediVisna()
    {
        return $this->checkForMaediVisna;
    }

    /**
     * Get checkForScrapie
     *
     * @return boolean
     */
    public function getCheckForScrapie()
    {
        return $this->checkForScrapie;
    }

    /**
     * @return string
     */
    public function getOriginScrapieStatus()
    {
        return $this->originScrapieStatus;
    }

    /**
     * @param string $originScrapieStatus
     */
    public function setOriginScrapieStatus($originScrapieStatus)
    {
        $this->originScrapieStatus = $originScrapieStatus;
    }

    /**
     * @return string
     */
    public function getOriginMaediVisnaStatus()
    {
        return $this->originMaediVisnaStatus;
    }

    /**
     * @param string $originMaediVisnaStatus
     */
    public function setOriginMaediVisnaStatus($originMaediVisnaStatus)
    {
        $this->originMaediVisnaStatus = $originMaediVisnaStatus;
    }

    /**
     * @return string
     */
    public function getOriginCaseousLymphadenitisStatus()
    {
        return $this->originCaseousLymphadenitisStatus;
    }

    /**
     * @param string $originCaseousLymphadenitisStatus
     */
    public function setOriginCaseousLymphadenitisStatus($originCaseousLymphadenitisStatus)
    {
        $this->originCaseousLymphadenitisStatus = $originCaseousLymphadenitisStatus;
    }

    /**
     * @return string
     */
    public function getOriginFootRotStatus()
    {
        return $this->originFootRotStatus;
    }

    /**
     * @param string $originFootRotStatus
     */
    public function setOriginFootRotStatus($originFootRotStatus)
    {
        $this->originFootRotStatus = $originFootRotStatus;
    }

    /**
     * @return string
     */
    public function getDestinationScrapieStatus()
    {
        return $this->destinationScrapieStatus;
    }

    /**
     * @param string $destinationScrapieStatus
     */
    public function setDestinationScrapieStatus($destinationScrapieStatus)
    {
        $this->destinationScrapieStatus = $destinationScrapieStatus;
    }

    /**
     * @return string
     */
    public function getDestinationMaediVisnaStatus()
    {
        return $this->destinationMaediVisnaStatus;
    }

    /**
     * @param string $destinationMaediVisnaStatus
     */
    public function setDestinationMaediVisnaStatus($destinationMaediVisnaStatus)
    {
        $this->destinationMaediVisnaStatus = $destinationMaediVisnaStatus;
    }

    /**
     * @return string
     */
    public function getDestinationCaseousLymphadenitisStatus()
    {
        return $this->destinationCaseousLymphadenitisStatus;
    }

    /**
     * @param string $destinationCaseousLymphadenitisStatus
     */
    public function setDestinationCaseousLymphadenitisStatus($destinationCaseousLymphadenitisStatus)
    {
        $this->destinationCaseousLymphadenitisStatus = $destinationCaseousLymphadenitisStatus;
    }

    /**
     * @return string
     */
    public function getDestinationFootRotStatus()
    {
        return $this->destinationFootRotStatus;
    }

    /**
     * @param string $destinationFootRotStatus
     */
    public function setDestinationFootRotStatus($destinationFootRotStatus)
    {
        $this->destinationFootRotStatus = $destinationFootRotStatus;
    }

    /**
     * @return \DateTime
     */
    public function getArrivalDate()
    {
        return $this->arrivalDate;
    }

    /**
     * @param \DateTime $arrivalDate
     */
    public function setArrivalDate($arrivalDate)
    {
        $this->arrivalDate = $arrivalDate;
    }

    /**
     * @return MaediVisna
     */
    public function getMaediVisna()
    {
        return $this->maediVisna;
    }

    /**
     * @param MaediVisna $maediVisna
     */
    public function setMaediVisna($maediVisna)
    {
        $this->maediVisna = $maediVisna;
    }

    /**
     * @return Scrapie
     */
    public function getScrapie()
    {
        return $this->scrapie;
    }


    /**
     * @param Scrapie $scrapie
     */
    public function setScrapie($scrapie)
    {
        $this->scrapie = $scrapie;
    }

    /**
     * @return CaseousLymphadenitis
     */
    public function getCaseousLymphadenitis()
    {
        return $this->caseousLymphadenitis;
    }

    /**
     * @param CaseousLymphadenitis $caseousLymphadenitis
     */
    public function setCaseousLymphadenitis($caseousLymphadenitis)
    {
        $this->caseousLymphadenitis = $caseousLymphadenitis;
    }

    /**
     * @return FootRot
     */
    public function getFootRot()
    {
        return $this->footRot;
    }

    /**
     * @param FootRot $footRot
     */
    public function setFootRot($footRot)
    {
        $this->footRot = $footRot;
    }



}
