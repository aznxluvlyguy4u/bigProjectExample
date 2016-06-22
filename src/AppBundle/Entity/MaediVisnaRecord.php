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
 * In Dutch maedi_visna is named 'zwoegerziekte'.
 *
 * Class MaediVisnaRecord
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MaediVisnaRecordRepository")
 * @ExclusionPolicy("all")
 */
class MaediVisnaRecord
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
    private $maediVisnaStatus;

    /**
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
     * @ORM\ManyToOne(targetEntity="LocationHealth", inversedBy="maediVisnaRecords")
     * @JMS\Type("AppBundle\Entity\LocationHealth")
     */
    private $locationHealth;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     * @Expose
     */
    private $hide;

    /**
     * MaediVisnaRecord constructor.
     */
    public function __construct()
    {
        $this->logDate(new DateTime('now'));
        $this->hide = false;
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
     * @return MaediVisnaRecord
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
     * Set maediVisnaStatus
     *
     * @param string $maediVisnaStatus
     *
     * @return MaediVisnaRecord
     */
    public function setMaediVisnaStatus($maediVisnaStatus)
    {
        $this->maediVisnaStatus = $maediVisnaStatus;

        return $this;
    }

    /**
     * Get maediVisnaStatus
     *
     * @return string
     */
    public function getMaediVisnaStatus()
    {
        return $this->maediVisnaStatus;
    }

    /**
     * Set maediVisnaEndDate
     *
     * @param \DateTime $maediVisnaEndDate
     *
     * @return MaediVisnaRecord
     */
    public function setMaediVisnaEndDate($maediVisnaEndDate)
    {
        $this->maediVisnaEndDate = $maediVisnaEndDate;

        return $this;
    }

    /**
     * Get maediVisnaEndDate
     *
     * @return \DateTime
     */
    public function getMaediVisnaEndDate()
    {
        return $this->maediVisnaEndDate;
    }

    /**
     * Set checkDate
     *
     * @param \DateTime $checkDate
     *
     * @return MaediVisnaRecord
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
     * Set hide
     *
     * @param boolean $hide
     *
     * @return MaediVisnaRecord
     */
    public function setHide($hide)
    {
        $this->hide = $hide;

        return $this;
    }

    /**
     * Get hide
     *
     * @return boolean
     */
    public function getHide()
    {
        return $this->hide;
    }

    /**
     * Set arrival
     *
     * @param \AppBundle\Entity\DeclareArrival $arrival
     *
     * @return MaediVisnaRecord
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
     * @return MaediVisnaRecord
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
     * @return MaediVisnaRecord
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
