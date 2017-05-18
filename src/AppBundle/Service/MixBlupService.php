<?php


namespace AppBundle\Service;


use AppBundle\Enumerator\MixBlupType;
use AppBundle\MixBlup\ExteriorProcess;
use AppBundle\MixBlup\LambMeatIndexProcess;
use AppBundle\MixBlup\MixBlupProcessInterface;
use AppBundle\MixBlup\ReproductionProcess;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

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

    /** @var MixBlupInputQueueService */
    private $queueService;

    /** @var string */
    private $currentEnvironment;

    /** @var string */
    private $cacheDir;
    
    /** @var string */
    private $workingFolder;

    /** @var array */
    private $mixBlupProcesses;

    /** @var string */
    private $jsonUploadMessage;

    /** @var Logger */
    private $logger;

    /**
     * MixBlupService constructor.
     * @param ObjectManager $em
     * @param AWSSimpleStorageService $s3Service
     * @param MixBlupInputQueueService $queueService
     * @param string $currentEnvironment
     * @param string $cacheDir
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, AWSSimpleStorageService $s3Service, MixBlupInputQueueService $queueService,
                                $currentEnvironment, $cacheDir, $logger = null)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->s3Service = $s3Service;
        $this->queueService = $queueService;
        $this->currentEnvironment = $currentEnvironment;
        $this->cacheDir = $cacheDir;
        $this->logger = $logger;
        $this->workingFolder = $cacheDir.'/'.MixBlupFolder::ROOT;

        $this->mixBlupProcesses = [];
        $this->mixBlupProcesses[MixBlupType::EXTERIOR] = new ExteriorProcess($em, $this->workingFolder);
        $this->mixBlupProcesses[MixBlupType::LAMB_MEAT_INDEX] = new LambMeatIndexProcess($em, $this->workingFolder);
        $this->mixBlupProcesses[MixBlupType::FERTILITY] = new ReproductionProcess($em, $this->workingFolder);
    }


    /** @return string */
    public function getWorkingFolder() { return $this->workingFolder; }


    /**
     * @return bool
     */
    public function writeInstructionFiles()
    {
        /**
         * @var string $mixBlupType
         * @var MixBlupProcessInterface $mixBlupProcess
         */
        foreach($this->mixBlupProcesses as $mixBlupType => $mixBlupProcess)
        {
            $this->logger->notice('Writing MixBlup instruction files for: '.$mixBlupType);
            $writeResult = $mixBlupProcess->writeInstructionFiles();
            if(!$writeResult) {
                $this->logger->critical('FAILED writing MixBlup instruction file for: '.$mixBlupType);
                return false;
            }
        }
        return true;
    }


    /**
     * @inheritDoc
     */
    public function run()
    {
        $writeResult = $this->write();
        if($writeResult) {
            $allUploadsSuccessful = $this->upload();
            $sendMessageResult = $this->sendMessage();

            if($sendMessageResult) {
                $this->deleteMixBlupFilesInCache();
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
            $this->logger->notice('Writing MixBlup input files for: '.$mixBlupType);
            $writeResult = $mixBlupProcess->write();
            if(!$writeResult) {
                $this->logger->critical('FAILED writing MixBlup input file for: '.$mixBlupType);
                return false;
            }
        }
        return true;
    }


    /**
     * Uploads the text files to the S3-Bucket
     *
     * @return boolean true is all uploads were successful
     */
    private function upload()
    {
        $key = TimeUtil::getTimeStampNow();
        $fileType = 'text/plain';

        $filesToUpload = [];
        $failedUploads = [];

        foreach ([$this->getDataFolder(), $this->getPedigreeFolder()] as $folderPath) {
            $this->logger->notice('Uploading files to S3 bucket from folder '.$folderPath);
            if ($handle = opendir($folderPath)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }

                    $currentFileLocation = $folderPath . '/' . $file;
                    $s3FilePath = MixBlupSetting::S3_MIXBLUP_INPUT_DIRECTORY . $key . '/' . $file;

                    $result = $this->s3Service->uploadFromFilePath($currentFileLocation, $s3FilePath, $fileType);

                    if ($result) {
                        $filesToUpload[] = $file;
                        $this->logger->notice('Succesfully uploaded '.$file);
                    } else {
                        $failedUploads[] = $file;
                        $this->logger->critical('FAILED uploading '.$file);
                    }
                }
            }
        }

        $messageToQueue = [
            "key" => $key,
            "files" => $filesToUpload,
            "failed_uploads" => $failedUploads
        ];

        if(count($failedUploads) > 0) {
            //TODO Log failed uploads and send an email notification
        }

        $this->jsonUploadMessage = json_encode($messageToQueue);
        $this->logger->notice('Upload message '.$this->jsonUploadMessage);

        return count($failedUploads) == 0;
    }


    /**
     * Send message to mixblup_input_queue to notify the cronjob to process the MixBlup input files.
     */
    private function sendMessage()
    {
        $sendToQresult = $this->queueService->send($this->jsonUploadMessage);
        $isSentSuccessfully = $sendToQresult['statusCode'] == '200';
        if($isSentSuccessfully) {
            $this->logger->notice('Upload message successfully sent to MixBlup queue');
        } else {
            $this->logger->critical('FAILED sending MixBlup upload message to queue!');
        }
        return $isSentSuccessfully;
    }


    /**
     *
     */
    private function deleteMixBlupFilesInCache()
    {
        FilesystemUtil::deleteAllFilesInFolders([
            $this->getDataFolder(),
            $this->getInstructionsFolder(),
            $this->getPedigreeFolder(),
        ]);
        $this->logger->notice('Generated MixBlup input files deleted from the workingfolder '.$this->workingFolder);
    }


    /** @return string */
    public function getDataFolder() { return $this->workingFolder."/".MixBlupFolder::DATA; }
    /** @return string */
    public function getInstructionsFolder() { return $this->workingFolder."/".MixBlupFolder::INSTRUCTIONS; }
    /** @return string */
    public function getPedigreeFolder() { return $this->workingFolder."/".MixBlupFolder::PEDIGREE; }
}