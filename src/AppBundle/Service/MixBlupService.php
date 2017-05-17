<?php


namespace AppBundle\Service;


use AppBundle\Enumerator\MixBlupType;
use AppBundle\MixBlup\ExteriorProcess;
use AppBundle\MixBlup\LambMeatIndexProcess;
use AppBundle\MixBlup\MixBlupProcessInterface;
use AppBundle\MixBlup\ReproductionProcess;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Util\FilesystemUtil;
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

    /** @var array */
    private $mixBlupProcesses;

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

        $this->mixBlupProcesses = [];
        $this->mixBlupProcesses[MixBlupType::EXTERIOR] = new ExteriorProcess($em, $this->workingFolder);
        $this->mixBlupProcesses[MixBlupType::LAMB_MEAT_INDEX] = new LambMeatIndexProcess($em, $this->workingFolder);
        $this->mixBlupProcesses[MixBlupType::FERTILITY] = new ReproductionProcess($em, $this->workingFolder);
    }


    /** @return string */
    public function getWorkingFolder() { return $this->workingFolder; }


    /**
     * @inheritDoc
     */
    public function run()
    {
        $writeResult = $this->write();
        if($writeResult) {
            $uploadResult = $this->upload();

            if($uploadResult) {
                $sendMessageResult = $this->sendMessage();

                if($sendMessageResult) {
                    $this->deleteMixBlupFilesInCache();
                }
            }
        }
        gc_collect_cycles();
    }

    
    /**
     * Writes the instructionFile-, dataFile- and pedigreeFile data to their respective text input files.
     * Note! Old files are automatically purged.
     */
    private function write()
    {
        /**
         * @var string $mixBlupType
         * @var MixBlupProcessInterface $mixBlupProcess
         */
        foreach($this->mixBlupProcesses as $mixBlupType => $mixBlupProcess)
        {
            $writeResult = $mixBlupProcess->write();
            if(!$writeResult) { return false; }
        }
        return true;
    }
    
    
    /**
     * Uploads the text files to the S3-Bucket
     */
    private function upload()
    {
        // TODO: Implement upload() method.
        return false;
    }


    /**
     * Send message to mixblup_input_queue to notify the cronjob to process the MixBlup input files.
     */
    private function sendMessage()
    {
        // TODO: Implement sendMessage() method.
        return false;
    }


    /**
     *
     */
    private function deleteMixBlupFilesInCache()
    {
        FilesystemUtil::deleteAllFilesInFolders([
            $this->workingFolder."/".MixBlupFolder::DATA,
            $this->workingFolder."/".MixBlupFolder::INSTRUCTIONS,
            $this->workingFolder."/".MixBlupFolder::PEDIGREE,
        ]);
    }
}