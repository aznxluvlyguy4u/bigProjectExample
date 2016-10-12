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
 * Class CaseousLymphadenitis
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CaseousLymphadenitisRepository")
 * @ExclusionPolicy("all")
 */
class CaseousLymphadenitis
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
     * @ORM\ManyToOne(targetEntity="LocationHealth", inversedBy="caseousLymphadenitis")
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
     * CaseousLymphadenitis constructor.
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
     * @return CaseousLymphadenitis
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
     * @return CaseousLymphadenitis
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
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return CaseousLymphadenitis
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
     * @return CaseousLymphadenitis
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
     * @return CaseousLymphadenitis
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
     * @return CaseousLymphadenitis
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
     * @return CaseousLymphadenitis
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
     * @return CaseousLymphadenitis
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
    public function isIsManualEdit()
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
        $this->reasonOfEdit = trim($reasonOfEdit);
    }
    
    
    
    
}
