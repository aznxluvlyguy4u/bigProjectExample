<?php


namespace AppBundle\Service;


use AppBundle\Cache\AnimalCacher;
use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Component\MixBlup\WormResistanceInputProcess;
use AppBundle\Constant\Environment;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Component\MixBlup\ExteriorInputProcess;
use AppBundle\Component\MixBlup\LambMeatIndexInputProcess;
use AppBundle\Component\MixBlup\MixBlupInputProcessInterface;
use AppBundle\Component\MixBlup\ReproductionInputProcess;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class MixBlupInputFilesService
 * @package AppBundle\Service
 */
class MixBlupInputFilesService implements MixBlupServiceInterface
{
    const LOCAL_FOLDER = 'local';

    /** @var Connection */
    private $conn;
    /** @var EntityManagerInterface */
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
    /** @var Filesystem */
    private $fs;

    /** @var string */
    private $onlyUseThisProcessType;

    /** @var boolean */
    private $duplicateResultsToLocalFolder;

    /**
     * MixBlupInputFilesService constructor.
     * @param EntityManagerInterface $em
     * @param AWSSimpleStorageService $s3Service
     * @param MixBlupInputQueueService $queueService
     * @param string $currentEnvironment
     * @param string $cacheDir
     * @param Logger $logger
     */
    public function __construct(EntityManagerInterface $em, AWSSimpleStorageService $s3Service, MixBlupInputQueueService $queueService,
                                $currentEnvironment, $cacheDir, $logger)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->s3Service = $s3Service;
        $this->queueService = $queueService;
        $this->currentEnvironment = $currentEnvironment;
        $this->cacheDir = $cacheDir;
        $this->logger = $logger;
        $this->workingFolder = $cacheDir.'/'.MixBlupFolder::ROOT;

        $this->onlyUseThisProcessType = null;

        $this->mixBlupProcesses = [];
        $this->mixBlupProcesses[MixBlupType::EXTERIOR] = new ExteriorInputProcess($em, $this->workingFolder, $this->logger);
        $this->mixBlupProcesses[MixBlupType::LAMB_MEAT_INDEX] = new LambMeatIndexInputProcess($em, $this->workingFolder, $this->logger);
        $this->mixBlupProcesses[MixBlupType::FERTILITY] = new ReproductionInputProcess($em, $this->workingFolder, $this->logger);
        $this->mixBlupProcesses[MixBlupType::WORM] = new WormResistanceInputProcess($em, $this->workingFolder, $this->logger);

        $this->setCachePurgeSettingByEnvironment();
    }


    protected function setCachePurgeSettingByEnvironment()
    {
        switch($this->currentEnvironment) {
            case Environment::PROD:     $this->duplicateResultsToLocalFolder = false; break;
            case Environment::STAGE:    $this->duplicateResultsToLocalFolder = false; break;
            case Environment::DEV:      $this->duplicateResultsToLocalFolder = true; break;
            case Environment::TEST:     $this->duplicateResultsToLocalFolder = true; break;
            case Environment::LOCAL:    $this->duplicateResultsToLocalFolder = true; break;
            default;                    $this->duplicateResultsToLocalFolder = false; break;
        }
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
         * @var MixBlupInputProcessInterface $mixBlupProcess
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


    public function runExterior()
    {
        $this->onlyUseThisProcessType = MixBlupType::EXTERIOR;
        $this->run();
        $this->onlyUseThisProcessType = null;
    }


    public function runLambMeatIndex()
    {
        $this->onlyUseThisProcessType = MixBlupType::LAMB_MEAT_INDEX;
        $this->run();
        $this->onlyUseThisProcessType = null;
    }


    public function runFertility()
    {
        $this->onlyUseThisProcessType = MixBlupType::FERTILITY;
        $this->run();
        $this->onlyUseThisProcessType = null;
    }


    public function runWorm()
    {
        $this->onlyUseThisProcessType = MixBlupType::WORM;
        $this->run();
        $this->onlyUseThisProcessType = null;
    }


    /**
     * Generates the data for all the files,
     * writes the data to the text input files,
     * uploads the text files to the S3-Bucket,
     * and sends a message to sqs queue with the overview data of the uploaded files.
     */
    public function run()
    {
        $this->updateAnimalIdAndDateValues();
        $this->generateMissingAnimalCacheRecords();
        $this->updateLitterDetails();
        $this->deleteMixBlupFilesInCache();

        $writeResult = $this->write();

        $this->deleteMixBlupFilesInCache();
        $this->fs = null;
        gc_collect_cycles();
    }


    /**
     * @return Filesystem
     */
    private function getFs()
    {
        if ($this->fs === null) {
            $this->fs = new Filesystem();
        }

        return $this->fs;
    }


    private function updateAnimalIdAndDateValues()
    {
        $updateCount = MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false);
        if($updateCount > 0) {
            $this->logger->notice($updateCount.' animalIdAndDate values in measurement table updated');
        }
    }


    private function generateMissingAnimalCacheRecords()
    {
        $updateCount = AnimalCacher::cacheAnimalsBySqlInsert($this->em, null);
        $countVal = $updateCount > 0 ? 'No' : $updateCount;
        $this->logger->notice($countVal.' missing animal_cache records inserted');
    }


    private function updateLitterDetails()
    {
        $this->logger->notice('Updating litter details...');
        $updateCount = GeneDiversityUpdater::updateAll($this->conn, false, null);
        $this->logger->notice($updateCount.' heterosis and recombination values updated');

        if ($this->runIncludesFertility() || $this->runIncludesWorm()) {
            $this->logger->notice(LitterUtil::matchMatchingMates($this->conn, false).' \'mate-litter\'s matched');
            $this->logger->notice(LitterUtil::removeMatesFromRevokedLitters($this->conn).' \'mate-litter\'s unmatched');

            $this->logger->notice(LitterUtil::updateLitterOrdinals($this->conn).' litterOrdinals updated');
            $this->logger->notice(LitterUtil::removeLitterOrdinalFromRevokedLitters($this->conn).' litterOrdinals removed from revoked litters');

            if ($this->runIncludesFertility()) {
                $this->logger->notice(LitterUtil::updateSuckleCount($this->conn).' suckleCounts updated');
                $this->logger->notice(LitterUtil::removeSuckleCountFromRevokedLitters($this->conn).' suckleCounts removed from revoked litters');
                $this->logger->notice(LitterUtil::updateGestationPeriods($this->conn).' gestationPeriods updated');
                $this->logger->notice(LitterUtil::updateBirthInterVal($this->conn).' birthIntervals updated');
            }
        }
    }

    
    /**
     * Writes the instructionFile-, dataFile- and pedigreeFile data to their respective text input files.
     * Note! Old files are automatically purged.
     */
    private function write()
    {
        if ($this->onlyUseThisProcessType) {
            return $this->writeProcess($this->onlyUseThisProcessType);

        } else {
            /**
             * @var string $mixBlupType
             * @var MixBlupInputProcessInterface $mixBlupProcess
             */
            foreach($this->mixBlupProcesses as $mixBlupType => $mixBlupProcess)
            {
                $processResult = $this->writeProcess($mixBlupType);
                if (!$processResult) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * @param string $mixBlupType
     * @return bool
     */
    private function writeProcess($mixBlupType)
    {
        /** @var MixBlupInputProcessInterface $mixBlupProcess */
        $mixBlupProcess = $this->mixBlupProcesses[$mixBlupType];

        $this->logger->notice('Writing MixBlup input files for: '.$mixBlupType);

        $this->deleteMixBlupFilesInCache();

        $writeResult = $mixBlupProcess->write();

        if(!$writeResult) {
            $this->logger->critical('FAILED writing MixBlup input file for: '.$mixBlupType);
            return false;
        }

        $allUploadsSuccessful = $this->upload();
        $sendMessageResult = $this->sendMessage();

        if ($this->duplicateResultsToLocalFolder) {
            $this->copyOutputToLocalFolder();
        }

        $this->deleteMixBlupFilesInCache();

        return true;
    }


    private function copyOutputToLocalFolder()
    {
        FilesystemUtil::recurseCopy($this->getDataFolder(),$this->getLocalDataFolder(), $this->getFs(), $this->logger);
        FilesystemUtil::recurseCopy($this->getPedigreeFolder(),$this->getLocalPedigreeFolder(), $this->getFs(), $this->logger);
        FilesystemUtil::recurseCopy($this->getInstructionsFolder(),$this->getLocalInstructionsFolder(), $this->getFs(), $this->logger);
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
        $instructionFilesToUpload = [];
        $failedUploads = [];

        foreach ([$this->getDataFolder(), $this->getPedigreeFolder(), $this->getInstructionsFolder()] as $folderPath) {
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
                        if($folderPath == $this->getInstructionsFolder()) {
                            $instructionFilesToUpload[] = $file;
                        } else {
                            $filesToUpload[] = $file;
                        }
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
            "instruction_files" => $instructionFilesToUpload,
            "failed_uploads" => $failedUploads,
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


    /** @return bool */
    private function runIncludesFertility() { return $this->runIncludesType(MixBlupType::FERTILITY); }
    /** @return bool */
    private function runIncludesLambMeatIndex() { return $this->runIncludesType(MixBlupType::LAMB_MEAT_INDEX); }
    /** @return bool */
    private function runIncludesExterior() { return $this->runIncludesType(MixBlupType::EXTERIOR); }
    /** @return bool */
    private function runIncludesWorm() { return $this->runIncludesType(MixBlupType::WORM); }

    /**
     * @param string $mixblupType
     * @return bool
     */
    private function runIncludesType($mixblupType)
    {
        return
                ($this->onlyUseThisProcessType === null && key_exists(MixBlupType::FERTILITY, $this->mixBlupProcesses))
                || $this->onlyUseThisProcessType === $mixblupType
            ;
    }

    /** @return string */
    public function getDataFolder() { return $this->workingFolder."/".MixBlupFolder::DATA; }
    /** @return string */
    public function getInstructionsFolder() { return $this->workingFolder."/".MixBlupFolder::INSTRUCTIONS; }
    /** @return string */
    public function getPedigreeFolder() { return $this->workingFolder."/".MixBlupFolder::PEDIGREE; }

    /** @return string */
    private function getLocalWorkingFolder() { return $this->workingFolder."/".self::LOCAL_FOLDER."/"; }

    /** @return string */
    public function getLocalDataFolder() { return $this->getLocalWorkingFolder().MixBlupFolder::DATA; }
    /** @return string */
    public function getLocalInstructionsFolder() { return $this->getLocalWorkingFolder().MixBlupFolder::INSTRUCTIONS; }
    /** @return string */
    public function getLocalPedigreeFolder() { return $this->getLocalWorkingFolder().MixBlupFolder::PEDIGREE; }
}