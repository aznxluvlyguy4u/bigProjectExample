<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ReportWorker
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ReportWorkerRepository")
 * @package AppBundle\Entity
 */
class ReportWorker
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $reportType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $fileType;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $downloadUrl;

    /**
     * @var Worker
     *
     * @ORM\OneToOne(targetEntity="Worker", inversedBy="pdfWorker")
     * @ORM\JoinColumn(name="worker_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    private $worker;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $hash;

    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ReportWorker
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
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
     * @return Worker
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * @param $worker
     * @return ReportWorker
     */
    public function setWorker($worker)
    {
        $this->worker = $worker;
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
}