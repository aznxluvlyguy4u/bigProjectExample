<?php

namespace AppBundle\Entity;

use \DateTime;
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
     * @ORM\OneToOne(targetEntity="Location", mappedBy="health", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     * @Expose
     */
    private $location;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    protected $logDate;

    /**
     * maedi_visna is 'zwoegerziekte' in Dutch
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $maediVisnaStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $scrapieStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $locationHealthStatus;

    /**
     * maedi_visna is 'zwoegerziekte' in Dutch
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $maediVisnaEndDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $scrapieEndDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $checkDate;

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
     * @return mixed
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param mixed $logDate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    }

    /**
     * @return string
     */
    public function getLocationHealthStatus()
    {
        return $this->locationHealthStatus;
    }

    /**
     * @param string $locationHealthStatus
     */
    public function setLocationHealthStatus($locationHealthStatus)
    {
        $this->locationHealthStatus = $locationHealthStatus;
    }

    /**
     * @return \DateTime
     */
    public function getMaediVisnaEndDate()
    {
        return $this->maediVisnaEndDate;
    }

    /**
     * @param \DateTime $maediVisnaEndDate
     */
    public function setMaediVisnaEndDate($maediVisnaEndDate)
    {
        $this->maediVisnaEndDate = $maediVisnaEndDate;
    }

    /**
     * @return \DateTime
     */
    public function getScrapieEndDate()
    {
        return $this->scrapieEndDate;
    }

    /**
     * @param \DateTime $scrapieEndDate
     */
    public function setScrapieEndDate($scrapieEndDate)
    {
        $this->scrapieEndDate = $scrapieEndDate;
    }

    /**
     * @return \DateTime
     */
    public function getCheckDate()
    {
        return $this->checkDate;
    }

    /**
     * @param \DateTime $checkDate
     */
    public function setCheckDate($checkDate)
    {
        $this->checkDate = $checkDate;
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
     * @return string
     */
    public function getMaediVisnaStatus()
    {
        return $this->maediVisnaStatus;
    }

    /**
     * @param string $maediVisnaStatus
     */
    public function setMaediVisnaStatus($maediVisnaStatus)
    {
        $this->maediVisnaStatus = $maediVisnaStatus;
    }

    /**
     * @return string
     */
    public function getScrapieStatus()
    {
        return $this->scrapieStatus;
    }

    /**
     * @param string $scrapieStatus
     */
    public function setScrapieStatus($scrapieStatus)
    {
        $this->scrapieStatus = $scrapieStatus;
    }

}
