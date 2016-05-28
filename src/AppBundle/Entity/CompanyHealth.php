<?php

namespace AppBundle\Entity;

use \DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class CompanyHealth
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CompanyHealthRepository")
 * @package AppBundle\Entity
 */
class CompanyHealth extends Health
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $companyHealthStatus;

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
     * @return string
     */
    public function getCompanyHealthStatus()
    {
        return $this->companyHealthStatus;
    }

    /**
     * @param string $companyHealthStatus
     */
    public function setCompanyHealthStatus($companyHealthStatus)
    {
        $this->companyHealthStatus = $companyHealthStatus;
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



}
