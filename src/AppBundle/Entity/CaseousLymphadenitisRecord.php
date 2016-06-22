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
 * Class CaseousLymphadenitisRecord
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CaseousLymphadenitisRecordRepository")
 * @ExclusionPolicy("all")
 */
class CaseousLymphadenitisRecord
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
    private $caseousLymphadenitisStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $caseousLymphadenitisEndDate;

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
     * @ORM\ManyToOne(targetEntity="LocationHealth", inversedBy="caseousLymphadenitisRecords")
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
     * CaseousLymphadenitisRecord constructor.
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
     * @return CaseousLymphadenitisRecord
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
     * Set caseousLymphadenitisStatus
     *
     * @param string $caseousLymphadenitisStatus
     *
     * @return CaseousLymphadenitisRecord
     */
    public function setCaseousLymphadenitisStatus($caseousLymphadenitisStatus)
    {
        $this->caseousLymphadenitisStatus = $caseousLymphadenitisStatus;

        return $this;
    }

    /**
     * Get caseousLymphadenitisStatus
     *
     * @return string
     */
    public function getCaseousLymphadenitisStatus()
    {
        return $this->caseousLymphadenitisStatus;
    }

    /**
     * Set caseousLymphadenitisEndDate
     *
     * @param \DateTime $caseousLymphadenitisEndDate
     *
     * @return CaseousLymphadenitisRecord
     */
    public function setCaseousLymphadenitisEndDate($caseousLymphadenitisEndDate)
    {
        $this->caseousLymphadenitisEndDate = $caseousLymphadenitisEndDate;

        return $this;
    }

    /**
     * Get caseousLymphadenitisEndDate
     *
     * @return \DateTime
     */
    public function getCaseousLymphadenitisEndDate()
    {
        return $this->caseousLymphadenitisEndDate;
    }

    /**
     * Set checkDate
     *
     * @param \DateTime $checkDate
     *
     * @return CaseousLymphadenitisRecord
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
     * @return CaseousLymphadenitisRecord
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
     * @return CaseousLymphadenitisRecord
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
     * @return CaseousLymphadenitisRecord
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
     * @return CaseousLymphadenitisRecord
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
