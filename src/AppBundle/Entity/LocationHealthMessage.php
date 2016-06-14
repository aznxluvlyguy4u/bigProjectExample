<?php

namespace AppBundle\Entity;


use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
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
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     * @Expose
     */
    private $checkForMaediVisna;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Assert\NotBlank
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
     * @ORM\Column(type="string")
     * @Assert\NotBlank
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
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return LocationHealthMessage
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }
}
