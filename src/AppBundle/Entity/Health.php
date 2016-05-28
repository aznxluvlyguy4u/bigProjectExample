<?php

namespace AppBundle\Entity;


abstract class Health
{
    /**
     * maedi_visna is 'zwoegerziekte' in Dutch
     *
     * @var string
     */
    private $maediVisnaStatus;

    /**
     * @var string
     */
    private $scrapieStatus;

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