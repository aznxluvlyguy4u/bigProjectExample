<?php

namespace AppBundle\Entity;

use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class LocationHealth
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationHealthRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class LocationHealth
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
     * @ORM\OneToMany(targetEntity="MaediVisnaRecord", mappedBy="locationHealth")
     */
    private $maediVisnaRecords;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ScrapieRecord", mappedBy="locationHealth")
     */
    private $scrapieRecords;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CaseousLymphadenitisRecord", mappedBy="locationHealth")
     */
    private $caseousLymphadenitisRecords;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FootRotRecord", mappedBy="locationHealth")
     */
    private $footRotRecords;

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
        $this->isRevoked = false;

        $this->maediVisnaRecords = new ArrayCollection();
        $this->scrapieRecords = new ArrayCollection();
        $this->caseousLymphadenitisRecords = new ArrayCollection();
        $this->footRotRecords = new ArrayCollection();
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
     * Add maediVisnaRecord
     *
     * @param \AppBundle\Entity\MaediVisnaRecord $maediVisnaRecord
     *
     * @return LocationHealth
     */
    public function addMaediVisnaRecord(\AppBundle\Entity\MaediVisnaRecord $maediVisnaRecord)
    {
        $this->maediVisnaRecords[] = $maediVisnaRecord;

        return $this;
    }

    /**
     * Remove maediVisnaRecord
     *
     * @param \AppBundle\Entity\MaediVisnaRecord $maediVisnaRecord
     */
    public function removeMaediVisnaRecord(\AppBundle\Entity\MaediVisnaRecord $maediVisnaRecord)
    {
        $this->maediVisnaRecords->removeElement($maediVisnaRecord);
    }

    /**
     * Get maediVisnaRecords
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMaediVisnaRecords()
    {
        return $this->maediVisnaRecords;
    }

    /**
     * Add scrapieRecord
     *
     * @param \AppBundle\Entity\ScrapieRecord $scrapieRecord
     *
     * @return LocationHealth
     */
    public function addScrapieRecord(\AppBundle\Entity\ScrapieRecord $scrapieRecord)
    {
        $this->scrapieRecords[] = $scrapieRecord;

        return $this;
    }

    /**
     * Remove scrapieRecord
     *
     * @param \AppBundle\Entity\ScrapieRecord $scrapieRecord
     */
    public function removeScrapieRecord(\AppBundle\Entity\ScrapieRecord $scrapieRecord)
    {
        $this->scrapieRecords->removeElement($scrapieRecord);
    }

    /**
     * Get scrapieRecords
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getScrapieRecords()
    {
        return $this->scrapieRecords;
    }

    /**
     * Add caseousLymphadenitisRecord
     *
     * @param \AppBundle\Entity\CaseousLymphadenitisRecord $caseousLymphadenitisRecord
     *
     * @return LocationHealth
     */
    public function addCaseousLymphadenitisRecord(\AppBundle\Entity\CaseousLymphadenitisRecord $caseousLymphadenitisRecord)
    {
        $this->caseousLymphadenitisRecords[] = $caseousLymphadenitisRecord;

        return $this;
    }

    /**
     * Remove caseousLymphadenitisRecord
     *
     * @param \AppBundle\Entity\CaseousLymphadenitisRecord $caseousLymphadenitisRecord
     */
    public function removeCaseousLymphadenitisRecord(\AppBundle\Entity\CaseousLymphadenitisRecord $caseousLymphadenitisRecord)
    {
        $this->caseousLymphadenitisRecords->removeElement($caseousLymphadenitisRecord);
    }

    /**
     * Get caseousLymphadenitisRecords
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCaseousLymphadenitisRecords()
    {
        return $this->caseousLymphadenitisRecords;
    }

    /**
     * Add footRotRecord
     *
     * @param \AppBundle\Entity\FootRotRecord $footRotRecord
     *
     * @return LocationHealth
     */
    public function addFootRotRecord(\AppBundle\Entity\FootRotRecord $footRotRecord)
    {
        $this->footRotRecords[] = $footRotRecord;

        return $this;
    }

    /**
     * Remove footRotRecord
     *
     * @param \AppBundle\Entity\FootRotRecord $footRotRecord
     */
    public function removeFootRotRecord(\AppBundle\Entity\FootRotRecord $footRotRecord)
    {
        $this->footRotRecords->removeElement($footRotRecord);
    }

    /**
     * Get footRotRecords
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFootRotRecords()
    {
        return $this->footRotRecords;
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
}
