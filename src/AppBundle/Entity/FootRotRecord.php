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
 * Class FootRotRecord
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\FootRotRecordRepository")
 * @ExclusionPolicy("all")
 */
class FootRotRecord
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
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $footRotStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $footRotEndDate;

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
     * @ORM\OneToOne(targetEntity="DeclareArrival")
     * @ORM\JoinColumn(name="arrival_id", referencedColumnName="id")
     */
    private $arrival;

    /**
     * @ORM\OneToOne(targetEntity="DeclareImport")
     * @ORM\JoinColumn(name="import_id", referencedColumnName="id")
     */
    private $import;

    /**
     * @ORM\ManyToOne(targetEntity="LocationHealth", inversedBy="footRotRecords")
     * @JMS\Type("AppBundle\Entity\LocationHealth")
     */
    private $locationHealth;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     * @Expose
     */
    private $isHidden;

    /**
     * FootRotRecord constructor.
     */
    public function __construct()
    {
        $this->logDate(new DateTime('now'));
        $this->isHidden = false;
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
     * @return FootRotRecord
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
     * Set footRotStatus
     *
     * @param string $footRotStatus
     *
     * @return FootRotRecord
     */
    public function setFootRotStatus($footRotStatus)
    {
        $this->footRotStatus = $footRotStatus;

        return $this;
    }

    /**
     * Get footRotStatus
     *
     * @return string
     */
    public function getFootRotStatus()
    {
        return $this->footRotStatus;
    }

    /**
     * Set footRotEndDate
     *
     * @param \DateTime $footRotEndDate
     *
     * @return FootRotRecord
     */
    public function setFootRotEndDate($footRotEndDate)
    {
        $this->footRotEndDate = $footRotEndDate;

        return $this;
    }

    /**
     * Get footRotEndDate
     *
     * @return \DateTime
     */
    public function getFootRotEndDate()
    {
        return $this->footRotEndDate;
    }

    /**
     * Set checkDate
     *
     * @param \DateTime $checkDate
     *
     * @return FootRotRecord
     */
    public function setCheckDate($checkDate)
    {
        $this->checkDate = $checkDate;

        return $this;
    }

    /**
     * Get checkDate
     *
     * @return \DateTime
     */
    public function getCheckDate()
    {
        return $this->checkDate;
    }

    /**
     * Set isHidden
     *
     * @param boolean $isHidden
     *
     * @return FootRotRecord
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden
     *
     * @return boolean
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     * Set arrival
     *
     * @param \AppBundle\Entity\DeclareArrival $arrival
     *
     * @return FootRotRecord
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
     * @return FootRotRecord
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
     * Set locationHealth
     *
     * @param \AppBundle\Entity\LocationHealth $locationHealth
     *
     * @return FootRotRecord
     */
    public function setLocationHealth(\AppBundle\Entity\LocationHealth $locationHealth = null)
    {
        $this->locationHealth = $locationHealth;

        return $this;
    }

    /**
     * Get locationHealth
     *
     * @return \AppBundle\Entity\LocationHealth
     */
    public function getLocationHealth()
    {
        return $this->locationHealth;
    }
}
