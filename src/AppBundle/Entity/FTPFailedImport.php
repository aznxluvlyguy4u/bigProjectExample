<?php

namespace AppBundle\Entity;

use \DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class FTPFailedImport
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\FTPFailedImportRepository")
 */
class FTPFailedImport
{
    //Do not use the EntityClassInfo trait here, because this Class name violates convention.

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Exclude
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $failedImportId;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $filename;

    /**
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $url;

    /**
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $serverName;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    private $illness;

    /**
     * FTPFailedImport constructor.
     */
    public function __construct()
    {
        $this->logDate = new \DateTime();
        $this->failedImportId = uniqid(mt_rand(0,9999999));
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
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param mixed $logDate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    }

    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param mixed $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getServerName()
    {
        return $this->serverName;
    }

    /**
     * @param mixed $serverName
     */
    public function setServerName($serverName)
    {
        $this->serverName = $serverName;
    }

    /**
     * @return mixed
     */
    public function getIllness()
    {
        return $this->illness;
    }

    /**
     * @param mixed $illness
     */
    public function setIllness($illness)
    {
        $this->illness = $illness;
    }

    /**
     * @return mixed
     */
    public function getFailedImportId()
    {
        return $this->failedImportId;
    }

    /**
     * @param mixed $failedImportId
     */
    public function setFailedImportId($failedImportId)
    {
        $this->failedImportId = $failedImportId;
    }
}