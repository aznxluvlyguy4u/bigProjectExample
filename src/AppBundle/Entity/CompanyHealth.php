<?php

namespace AppBundle\Entity;


class CompanyHealth extends Health
{
    /**
     * @var string
     */
    private $companyHealthStatus;

    /**
     * maedi_visna is 'zwoegerziekte' in Dutch
     *
     * @var \DateTime
     */
    private $maediVisnaEndDate;

    /**
     * @var \DateTime
     */
    private $scrapieEndDate;

    /**
     * @var \DateTime
     */
    private $checkDate;

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