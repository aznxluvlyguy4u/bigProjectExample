<?php


namespace AppBundle\Service;


use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupSetting;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Driver\PDOSqlsrv\Connection;

/**
 * Class MixBlupService
 * @package AppBundle\Service
 */
class MixBlupService implements MixBlupServiceInterface
{
    /** @var Connection */
    private $conn;

    /** @var ObjectManager */
    private $em;

    /** @var AWSSimpleStorageService */
    private $s3Service;

    /** @var AWSQueueService */
    private $queueService;

    /** @var string */
    private $currentEnvironment;

    /** @var string */
    private $cacheDir;
    
    /** @var string */
    private $workingFolder;

    /**
     * MixBlupService constructor.
     * @param ObjectManager $em
     * @param AWSSimpleStorageService $s3Service
     * @param AWSQueueService $queueService
     * @param string $currentEnvironment
     * @param string $cacheDir
     */
    public function __construct(ObjectManager $em, AWSSimpleStorageService $s3Service, AWSQueueService $queueService,
                                $currentEnvironment, $cacheDir)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->s3Service = $s3Service;
        $this->queueService = $queueService;
        $this->currentEnvironment = $currentEnvironment;
        $this->cacheDir = $cacheDir;
        $this->workingFolder = $cacheDir.'/'.MixBlupFolder::ROOT;
    }


    /** @return string */
    public function getWorkingFolder() { return $this->workingFolder; }


    /**
     * @inheritDoc
     */
    public function run()
    {
        // TODO: Implement run() method.
    }

    /**
     * Uploads the text files to the S3-Bucket
     */
    private function upload()
    {
        // TODO: Implement upload() method.
    }


    /**
     * Writes the instructionFile-, dataFile- and pedigreeFile data to their respective text input files.
     */
    private function write()
    {
        // TODO: Implement write() method. Call write() methods from MixBlupDataFile classes.
        //Loop foreach(MixBlubDataFileInterface) { $foo->write(); }
    }


}