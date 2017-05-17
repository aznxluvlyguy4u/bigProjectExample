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

    /**
     * MixBlupService constructor.
     * @param ObjectManager $em
     * @param AWSSimpleStorageService $s3Service
     * @param MixBlupInputQueueService $queueService
     * @param string $currentEnvironment
     * @param string $cacheDir
     */
    public function __construct(ObjectManager $em, AWSSimpleStorageService $s3Service, MixBlupInputQueueService $queueService,
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
            $writeResult = $mixBlupProcess->write();
            if(!$writeResult) { return false; }
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
                    } else {
                        $failedUploads[] = $file;
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

        return count($failedUploads) == 0;
    }


    /**
     * Send message to mixblup_input_queue to notify the cronjob to process the MixBlup input files.
     */
    private function sendMessage()
    {
        $sendToQresult = $this->queueService->send($this->jsonUploadMessage);
        return $sendToQresult['statusCode'] == '200';
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
    }


    /** @return string */
    public function getDataFolder() { return $this->workingFolder."/".MixBlupFolder::DATA; }
    /** @return string */
    public function getInstructionsFolder() { return $this->workingFolder."/".MixBlupFolder::INSTRUCTIONS; }
    /** @return string */
    public function getPedigreeFolder() { return $this->workingFolder."/".MixBlupFolder::PEDIGREE; }
}