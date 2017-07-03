<?php

namespace AppBundle\Report;


use AppBundle\Entity\Client;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class ReportBase
{
    /** @var ObjectManager */
    protected $em;
    /** @var Connection */
    protected $conn;
    /** @var Client */
    protected $client;
    /** @var String */
    protected $fileNameType;

    /**
     * ReportBase constructor.
     * @param ObjectManager $em
     * @param Client $client
     * @param string $fileNameType
     */
    public function __construct(ObjectManager $em, $client, $fileNameType)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->client = $client;
        $this->fileNameType = $fileNameType;
    }


    /**
     * @param string $mainDirectory
     * @return string
     */
    public function getFilePath($mainDirectory)
    {
        return $mainDirectory.'/'.$this->getS3Key();
    }

    public function getFileName()
    {
        $dateTimeNow = new \DateTime();
        $datePrint = $dateTimeNow->format('Y-m-d_').$dateTimeNow->format('H').'h'.$dateTimeNow->format('i').'m'.$dateTimeNow->format('s').'s';

        return $this->fileNameType.'-'.$datePrint.'.pdf';
    }

    public function getS3Key()
    {
        $folderName = $this->client != null ? $this->client->getPersonId() : 'admin';
        return 'reports/'.$folderName.'/'.$this->getFileName();
    }

}