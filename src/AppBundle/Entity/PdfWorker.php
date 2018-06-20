<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PdfWorker
 * @ORM\Entity(repositoryClass="AppBundle\Entity\WorkerRepository")
 * @package AppBundle\Entity
 */
class PdfWorker
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
    private $pdfType;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $downloadUrl;

    /**
     * @var Worker
     *
     * @ORM\OneToOne(targetEntity="Worker", fetch="LAZY")
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
     * @return PdfWorker
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getPdfType()
    {
        return $this->pdfType;
    }

    /**
     * @param $pdfType
     * @return PdfWorker
     */
    public function setPdfType($pdfType)
    {
        $this->pdfType = $pdfType;
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
     * @return PdfWorker
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
     * @return PdfWorker
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
     * @return PdfWorker
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }
}