<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class LocationHealth
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationHealthRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class LocationHealth
{
    use EntityClassInfo;

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
     * @var Location
     *
     * @ORM\OneToOne(targetEntity="Location", mappedBy="locationHealth")
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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="MaediVisna", mappedBy="locationHealth")
     * @ORM\OrderBy({"checkDate" = "ASC"})
     */
    private $maediVisnas;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Scrapie", mappedBy="locationHealth")
     * @ORM\OrderBy({"checkDate" = "ASC"})
     */
    private $scrapies;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CaseousLymphadenitis", mappedBy="locationHealth")
     * @ORM\OrderBy({"checkDate" = "ASC"})
     */
    private $caseousLymphadenitis;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FootRot", mappedBy="locationHealth")
     * @ORM\OrderBy({"checkDate" = "ASC"})
     */
    private $footRots;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $currentScrapieStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $currentScrapieEndDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $currentMaediVisnaStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $currentMaediVisnaEndDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $currentCaseousLymphadenitisStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $currentCaseousLymphadenitisEndDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $currentFootRotStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $currentFootRotEndDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $locationHealthStatus;

    /**
     * LocationHealth constructor.
     */
    public function __construct()
    {
        $this->logDate = new DateTime('now');

        $this->maediVisnas = new ArrayCollection();
        $this->scrapies = new ArrayCollection();
        $this->caseousLymphadenitis = new ArrayCollection();
        $this->footRots = new ArrayCollection();
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
     * @return LocationHealth
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
     * Set currentScrapieStatus
     *
     * @param string $currentScrapieStatus
     *
     * @return LocationHealth
     */
    public function setCurrentScrapieStatus($currentScrapieStatus)
    {
        $this->currentScrapieStatus = $currentScrapieStatus;

        return $this;
    }

    /**
     * Get currentScrapieStatus
     *
     * @return string
     */
    public function getCurrentScrapieStatus()
    {
        return $this->currentScrapieStatus;
    }

    /**
     * Set currentScrapieEndDate
     *
     * @param \DateTime $currentScrapieEndDate
     *
     * @return LocationHealth
     */
    public function setCurrentScrapieEndDate($currentScrapieEndDate)
    {
        $this->currentScrapieEndDate = $currentScrapieEndDate;

        return $this;
    }

    /**
     * Get currentScrapieEndDate
     *
     * @return \DateTime
     */
    public function getCurrentScrapieEndDate()
    {
        return $this->currentScrapieEndDate;
    }

    /**
     * Set currentMaediVisnaStatus
     *
     * @param string $currentMaediVisnaStatus
     *
     * @return LocationHealth
     */
    public function setCurrentMaediVisnaStatus($currentMaediVisnaStatus)
    {
        $this->currentMaediVisnaStatus = $currentMaediVisnaStatus;

        return $this;
    }

    /**
     * Get currentMaediVisnaStatus
     *
     * @return string
     */
    public function getCurrentMaediVisnaStatus()
    {
        return $this->currentMaediVisnaStatus;
    }

    /**
     * Set currentMaediVisnaEndDate
     *
     * @param \DateTime $currentMaediVisnaEndDate
     *
     * @return LocationHealth
     */
    public function setCurrentMaediVisnaEndDate($currentMaediVisnaEndDate)
    {
        $this->currentMaediVisnaEndDate = $currentMaediVisnaEndDate;

        return $this;
    }

    /**
     * Get currentMaediVisnaEndDate
     *
     * @return \DateTime
     */
    public function getCurrentMaediVisnaEndDate()
    {
        return $this->currentMaediVisnaEndDate;
    }

    /**
     * Set currentCaseousLymphadenitisStatus
     *
     * @param string $currentCaseousLymphadenitisStatus
     *
     * @return LocationHealth
     */
    public function setCurrentCaseousLymphadenitisStatus($currentCaseousLymphadenitisStatus)
    {
        $this->currentCaseousLymphadenitisStatus = $currentCaseousLymphadenitisStatus;

        return $this;
    }

    /**
     * Get currentCaseousLymphadenitisStatus
     *
     * @return string
     */
    public function getCurrentCaseousLymphadenitisStatus()
    {
        return $this->currentCaseousLymphadenitisStatus;
    }

    /**
     * Set currentCaseousLymphadenitisEndDate
     *
     * @param \DateTime $currentCaseousLymphadenitisEndDate
     *
     * @return LocationHealth
     */
    public function setCurrentCaseousLymphadenitisEndDate($currentCaseousLymphadenitisEndDate)
    {
        $this->currentCaseousLymphadenitisEndDate = $currentCaseousLymphadenitisEndDate;

        return $this;
    }

    /**
     * Get currentCaseousLymphadenitisEndDate
     *
     * @return \DateTime
     */
    public function getCurrentCaseousLymphadenitisEndDate()
    {
        return $this->currentCaseousLymphadenitisEndDate;
    }

    /**
     * Set currentFootRotStatus
     *
     * @param string $currentFootRotStatus
     *
     * @return LocationHealth
     */
    public function setCurrentFootRotStatus($currentFootRotStatus)
    {
        $this->currentFootRotStatus = $currentFootRotStatus;

        return $this;
    }

    /**
     * Get currentFootRotStatus
     *
     * @return string
     */
    public function getCurrentFootRotStatus()
    {
        return $this->currentFootRotStatus;
    }

    /**
     * Set currentFootRotEndDate
     *
     * @param \DateTime $currentFootRotEndDate
     *
     * @return LocationHealth
     */
    public function setCurrentFootRotEndDate($currentFootRotEndDate)
    {
        $this->currentFootRotEndDate = $currentFootRotEndDate;

        return $this;
    }

    /**
     * Get currentFootRotEndDate
     *
     * @return \DateTime
     */
    public function getCurrentFootRotEndDate()
    {
        return $this->currentFootRotEndDate;
    }

    /**
     * Set locationHealthStatus
     *
     * @param string $locationHealthStatus
     *
     * @return LocationHealth
     */
    public function setLocationHealthStatus($locationHealthStatus)
    {
        $this->locationHealthStatus = $locationHealthStatus;

        return $this;
    }

    /**
     * Get locationHealthStatus
     *
     * @return string
     */
    public function getLocationHealthStatus()
    {
        return $this->locationHealthStatus;
    }

    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return LocationHealth
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
     * Add maediVisna
     *
     * @param \AppBundle\Entity\MaediVisna $maediVisna
     *
     * @return LocationHealth
     */
    public function addMaediVisna(\AppBundle\Entity\MaediVisna $maediVisna)
    {
        $this->maediVisnas[] = $maediVisna;

        return $this;
    }

    /**
     * Remove maediVisna
     *
     * @param \AppBundle\Entity\MaediVisna $maediVisna
     */
    public function removeMaediVisna(\AppBundle\Entity\MaediVisna $maediVisna)
    {
        $this->maediVisnas->removeElement($maediVisna);
    }

    /**
     * Get maediVisnas
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMaediVisnas()
    {
        return $this->maediVisnas;
    }

    /**
     * Add scrapie
     *
     * @param Scrapie $scrapie
     *
     * @return LocationHealth
     */
    public function addScrapie($scrapie)
    {
        $this->scrapies[] = $scrapie;

        return $this;
    }

    /**
     * Remove scrapie
     *
     * @param Scrapie $scrapie
     */
    public function removeScrapie($scrapie)
    {
        $this->scrapies->removeElement($scrapie);
    }

    /**
     * Get scrapies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getScrapies()
    {
        return $this->scrapies;
    }

    /**
     * Add caseousLymphadeniti
     *
     * @param \AppBundle\Entity\CaseousLymphadenitis $caseousLymphadeniti
     *
     * @return LocationHealth
     */
    public function addCaseousLymphadeniti(\AppBundle\Entity\CaseousLymphadenitis $caseousLymphadeniti)
    {
        $this->caseousLymphadenitis[] = $caseousLymphadeniti;

        return $this;
    }

    /**
     * Remove caseousLymphadeniti
     *
     * @param \AppBundle\Entity\CaseousLymphadenitis $caseousLymphadeniti
     */
    public function removeCaseousLymphadeniti(\AppBundle\Entity\CaseousLymphadenitis $caseousLymphadeniti)
    {
        $this->caseousLymphadenitis->removeElement($caseousLymphadeniti);
    }

    /**
     * Get caseousLymphadenitis
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCaseousLymphadenitis()
    {
        return $this->caseousLymphadenitis;
    }

    /**
     * Add footRot
     *
     * @param \AppBundle\Entity\FootRot $footRot
     *
     * @return LocationHealth
     */
    public function addFootRot(\AppBundle\Entity\FootRot $footRot)
    {
        $this->footRots[] = $footRot;

        return $this;
    }

    /**
     * Remove footRot
     *
     * @param \AppBundle\Entity\FootRot $footRot
     */
    public function removeFootRot(\AppBundle\Entity\FootRot $footRot)
    {
        $this->footRots->removeElement($footRot);
    }

    /**
     * Get footRots
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFootRots()
    {
        return $this->footRots;
    }

    /**
     * Add scrapy
     *
     * @param \AppBundle\Entity\Scrapie $scrapy
     *
     * @return LocationHealth
     */
    public function addScrapy(\AppBundle\Entity\Scrapie $scrapy)
    {
        $this->scrapies[] = $scrapy;

        return $this;
    }

    /**
     * Remove scrapy
     *
     * @param \AppBundle\Entity\Scrapie $scrapy
     */
    public function removeScrapy(\AppBundle\Entity\Scrapie $scrapy)
    {
        $this->scrapies->removeElement($scrapy);
    }


    /**
     * @return bool
     */
    public function getAnimalHealthSubscription()
    {
        return $this->getLocation() && $this->getLocation()->getAnimalHealthSubscription();
    }


    /**
     * @param DateTime $checkDate
     * @param string $status
     */
    public function createDefaultMaediVisna(\DateTime $checkDate, string $status = MaediVisnaStatus::UNDER_OBSERVATION)
    {
        $maediVisna = new MaediVisna($status);
        $maediVisna->setCheckDate($checkDate);
        $maediVisna->setLocationHealth($this);

        $this->addMaediVisna($maediVisna);
        $this->setCurrentMaediVisnaStatus($status);
    }


    /**
     * @param DateTime $checkDate
     * @param string $status
     */
    public function createDefaultScrapie(\DateTime $checkDate, string $status = ScrapieStatus::UNDER_OBSERVATION)
    {
        $scrapie = new Scrapie($status);
        $scrapie->setCheckDate($checkDate);
        $scrapie->setLocationHealth($this);

        $this->addScrapie($scrapie);
        $this->setCurrentScrapieStatus($status);
    }


    /**
     * @return bool
     */
    public function hasNonBlankScrapieStatus(): bool
    {
        return $this->getCurrentScrapieStatus() !== ScrapieStatus::BLANK && $this->currentScrapieStatus !== null;
    }
}
