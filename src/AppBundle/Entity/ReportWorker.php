<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\WorkerType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ReportWorker
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ReportWorkerRepository")
 * @package AppBundle\Entity
 */
class ReportWorker extends Worker
{
    use EntityClassInfo;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $reportType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $fileType;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $downloadUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $locale;

    public function __construct()
    {
        parent::__construct();
        $this->setWorkerType(WorkerType::REPORT);
    }

    /**
     * @return int
     */
    public function getReportType()
    {
        return $this->reportType;
    }

    /**
     * @param $reportType
     * @return ReportWorker
     */
    public function setReportType($reportType)
    {
        $this->reportType = $reportType;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param $fileType
     * @return $this
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDownloadUrl()
    {
        return $this->getDownloadUrl();
    }

    /**
     * @param $url
     * @return ReportWorker
     */
    public function setDownloadUrl($url)
    {
        $this->downloadUrl = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param $hash
     * @return ReportWorker
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return ReportWorker
     */
    public function setLocale(string $locale): ReportWorker
    {
        $this->locale = $locale;
        return $this;
    }


}