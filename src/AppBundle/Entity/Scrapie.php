<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\StringUtil;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Scrapie
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ScrapieRepository")
 * @ExclusionPolicy("all")
 */
class Scrapie
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
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $endDate;

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
    private $arrivalRequest;

    /**
     * @ORM\OneToOne(targetEntity="DeclareImport")
     * @ORM\JoinColumn(name="import_id", referencedColumnName="id")
     */
    private $importRequest;

    /**
     * @ORM\ManyToOne(targetEntity="LocationHealth", inversedBy="scrapies")
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
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @Expose
     */
    private $isManualEdit;

    
    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $reasonOfEdit;
    
    
    /**
     * Scrapie constructor.
     */
    public function __construct($status = null, $endDate = null)
    {
        $this->logDate = new DateTime();
        $this->isHidden = false;
        $this->isManualEdit = false;

        $this->status = $status;
        $this->endDate = $endDate;
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
     * @return Scrapie
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
     * Set status
     *
     * @param string $status
     *
     * @return Scrapie
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isStatusBlank()
    {
        return $this->status === null || $this->status === ScrapieStatus::BLANK;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Scrapie
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
     * Set checkDate
     *
     * @param \DateTime $checkDate
     *
     * @return Scrapie
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
     * @return Scrapie
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
     * Set arrivalRequest
     *
     * @param \AppBundle\Entity\DeclareArrival $arrivalRequest
     *
     * @return Scrapie
     */
    public function setArrivalRequest(\AppBundle\Entity\DeclareArrival $arrivalRequest = null)
    {
        $this->arrivalRequest = $arrivalRequest;

        return $this;
    }

    /**
     * Get arrivalRequest
     *
     * @return \AppBundle\Entity\DeclareArrival
     */
    public function getArrivalRequest()
    {
        return $this->arrivalRequest;
    }

    /**
     * Set importRequest
     *
     * @param \AppBundle\Entity\DeclareImport $importRequest
     *
     * @return Scrapie
     */
    public function setImportRequest(\AppBundle\Entity\DeclareImport $importRequest = null)
    {
        $this->importRequest = $importRequest;

        return $this;
    }

    /**
     * Get importRequest
     *
     * @return \AppBundle\Entity\DeclareImport
     */
    public function getImportRequest()
    {
        return $this->importRequest;
    }

    /**
     * Set locationHealth
     *
     * @param \AppBundle\Entity\LocationHealth $locationHealth
     *
     * @return Scrapie
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

    /**
     * @return boolean
     */
    public function getIsManualEdit()
    {
        return $this->isManualEdit;
    }

    /**
     * @param boolean $isManualEdit
     */
    public function setIsManualEdit($isManualEdit)
    {
        $this->isManualEdit = $isManualEdit;
    }

    /**
     * @return string
     */
    public function getReasonOfEdit()
    {
        return $this->reasonOfEdit;
    }

    /**
     * @param string $reasonOfEdit
     */
    public function setReasonOfEdit($reasonOfEdit)
    {
        $this->reasonOfEdit = StringUtil::trimIfNotNull($reasonOfEdit);
    }
    
    
    
    
}
