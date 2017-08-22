<?php

namespace AppBundle\Report;


use AppBundle\Entity\Client;
use AppBundle\Enumerator\FileType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class ReportBase
{
    const DEFAULT_FILE_TYPE = FileType::PDF;

    /** @var ObjectManager|EntityManagerInterface */
    protected $em;
    /** @var Connection */
    protected $conn;
    /** @var Client */
    protected $client;
    /** @var String */
    protected $fileNameType;
    /** @var string */
    protected $fileNameWithoutExtension;
    /** @var string */
    protected $extension;

    /**
     * ReportBase constructor.
     * @param ObjectManager $em
     * @param Client $client
     * @param string $fileNameType
     */
    public function __construct(ObjectManager $em, $client = null, $fileNameType)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->client = $client;
        $this->fileNameType = $fileNameType;
        $this->extension = self::DEFAULT_FILE_TYPE;
    }


    /**
     * @param string $mainDirectory
     * @return string
     */
    public function getFilePath($mainDirectory)
    {
        return $mainDirectory.'/'.$this->getS3Key();
    }


    /**
     * @return string
     */
    public function getFileName()
    {
        if ($this->fileNameWithoutExtension === null) {
            $dateTimeNow = new \DateTime();
            $datePrint = $dateTimeNow->format('Y-m-d_').$dateTimeNow->format('H').'h'.$dateTimeNow->format('i').'m'.$dateTimeNow->format('s').'s';

            $this->fileNameWithoutExtension = $this->fileNameType.'-'.$datePrint;
        }

        return $this->fileNameWithoutExtension;
    }


    /**
     * @return string
     */
    public function getS3Key()
    {
        $folderName = $this->client != null ? $this->client->getPersonId() : 'admin';
        return 'reports/'.$folderName.'/'.$this->getFileName().'.'.$this->extension;
    }


    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     * @return ReportBase
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
        return $this;
    }




}