<?php


namespace AppBundle\Service;


use AppBundle\Constant\Filename;
use AppBundle\Enumerator\BreedValueType;
use AppBundle\Enumerator\MixBlupBreedValueType;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupInstructionFile;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\FilesystemUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class MixBlupOutputFilesService
 * @package AppBundle\Service
 */
class MixBlupOutputFilesService implements MixBlupServiceInterface
{
    const TEST_WITH_DOWNLOADED_ZIPS = false;
    const PURGE_ZIP_FOLDER_AFTER_SUCCESSFUL_RUN = false;
    const ONLY_UNZIP_SOLANI_AND_RELANI = false;

    /** @var Filesystem */
    private $fs;
    /** @var \ZipArchive */
    private $zip;
    /** @var Connection */
    private $conn;
    /** @var ObjectManager */
    private $em;
    /** @var AWSSimpleStorageService */
    private $s3Service;
    /** @var MixBlupOutputQueueService */
    private $queueService;
    /** @var Logger */
    private $logger;

    /** @var string */
    private $currentEnvironment;
    /** @var string */
    private $cacheDir;
    /** @var string */
    private $workingFolder;

    /** @var array */
    private $mixBlupProcesses;

    /** @var string */
    private $key;
    /** @var array */
    private $bulkFiles;
    /** @var array */
    private $relsol;
    /** @var array */
    private $errors;

    /** @var int */
    private $totalFilesToDownload;
    /** @var array */
    private $downloadedFileNames;
    /** @var array */
    private $failedDownloads;
    /** @var string */
    private $currentBreedType;

    /**
     * MixBlupOutputFilesService constructor.
     * @param ObjectManager $em
     * @param AWSSimpleStorageService $s3Service
     * @param MixBlupOutputQueueService $queueService
     * @param string $currentEnvironment
     * @param string $cacheDir
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, AWSSimpleStorageService $s3Service, MixBlupOutputQueueService $queueService,
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
        FilesystemUtil::createFolderPathIfNull([$this->getZipFolder(), $this->getResultsFolder()]);

        $this->fs = new Filesystem();
        $this->zip = new \ZipArchive();
        
        $this->mixBlupProcesses = [];
        //TODO include actual processes
        $this->mixBlupProcesses[BreedValueType::BIRTH] = null;
        $this->mixBlupProcesses[BreedValueType::EXTERIOR_LEG_WORK] = null;
        $this->mixBlupProcesses[BreedValueType::EXTERIOR_MUSCULARITY] = null;
        $this->mixBlupProcesses[BreedValueType::EXTERIOR_PROGRESS] = null;
        $this->mixBlupProcesses[BreedValueType::EXTERIOR_PROPORTION] = null;
        $this->mixBlupProcesses[BreedValueType::EXTERIOR_SKULL] = null;
        $this->mixBlupProcesses[BreedValueType::EXTERIOR_TYPE] = null;
        $this->mixBlupProcesses[BreedValueType::FERTILITY_1] = null;
        $this->mixBlupProcesses[BreedValueType::FERTILITY_2] = null;
        $this->mixBlupProcesses[BreedValueType::FERTILITY_3] = null;
        $this->mixBlupProcesses[BreedValueType::LAMB_MEAT] = null;
        $this->mixBlupProcesses[BreedValueType::TAIL_LENGTH] = null;
        $this->mixBlupProcesses[BreedValueType::WORM] = null;
    }


    public function run()
    {
        $this->processNextMessage();
    }
    
    
    public function test()
    {
        $this->purgeResultsFolder();
    }


    private function processNextMessage()
    {
        $response = $this->queueService->getNextMessage();
        $messageBody = AwsQueueServiceBase::getMessageBodyFromResponse($response);
        if ($messageBody) {
            $this->key = $messageBody->key;
            $this->bulkFiles = $messageBody->files;
            $this->relsol = $messageBody->relsol;
            $this->errors = $messageBody->errors;
            $blankBreedValueTypes = $this->errors;

            if(self::TEST_WITH_DOWNLOADED_ZIPS) {
                $downloadSuccessful = true;
            } else {
                $this->purgeZipFolder();
                $downloadSuccessful = $this->downloadZips();
            }

            if($downloadSuccessful)
            {
                $this->purgeResultsFolder();

                $unsuccessfulUnzips = [];
                $successfulUnzips = [];

                foreach($this->relsol as $zipFileName){

                    $this->purgeResultsFolder();
                    $this->unzipResultFiles($zipFileName);
                    $this->currentBreedType = self::getBreedValueTypeByRelSolZipName($zipFileName);

                    $successfulUnzip = file_exists($this->getResultsFolder() . Filename::SOLANI)
                                    && file_exists($this->getResultsFolder() . Filename::RELANI);

                    if($successfulUnzip) {

                        //TODO
                        $this->parseSolaniFiles();
                        $this->parseRelaniFiles();
                        $this->processBreedValues();
                        //TODO

                        $this->purgeResultsFolder();
                        $successfulUnzips[] = $zipFileName;
                    } else {
                        $unsuccessfulUnzips[] = $zipFileName;
                        $this->errors[] = $this->currentBreedType;
                    }
                }

                //TODO figure out how to deal with errors
                if(count($this->errors) == 0) {
                    $this->logger->notice('All breedValues processed successfully!');

                    if(self::PURGE_ZIP_FOLDER_AFTER_SUCCESSFUL_RUN) {
                        $this->purgeZipFolder();
                    }
                    $this->queueService->deleteMessage($response);

                } else {
                    $this->logger->error('The following breedValues had no relani nor solani file: '.implode(', ', $blankBreedValueTypes));
                    $this->logger->error('The following unzips failed: '.implode(', ', $unsuccessfulUnzips));
                    $this->logger->notice('The following unzips succeeded: '.implode(', ', $successfulUnzips));
                }

            } else {
                // Handle unsuccessful download
                $this->logger->error('Download of files from s3 bucket unsuccessful for key: '.$this->key);
                $this->logger->error('Download failed for these files: '.implode(', ', $this->failedDownloads));
                $this->logger->notice('Download succeeded for these files: '.implode(', ', $this->downloadedFileNames));
            }

        } else {
            $this->logger->notice('There is currently no message in the queue');
        }
        
    }


    /**
     * @return bool
     */
    private function downloadZips()
    {
        //Reset counters
        $this->downloadedFileNames = [];
        $this->failedDownloads = [];

        $this->totalFilesToDownload = self::ONLY_UNZIP_SOLANI_AND_RELANI ? count($this->relsol) : count($this->bulkFiles) + count($this->relsol);

        if(!self::ONLY_UNZIP_SOLANI_AND_RELANI) {
            // download all files
            foreach ($this->bulkFiles as $file) {
                $this->downloadZipFile($file);
            }
        }

        foreach($this->relsol as $file){
            $this->downloadZipFile($file);
        }

        return count($this->downloadedFileNames) == $this->totalFilesToDownload;
    }


    /**
     * @param $fileName
     */
    private function downloadZipFile($fileName)
    {
        $this->logger->notice('DOWNLOADING FILE ' . $fileName);

        $content = $this->s3Service->downloadFileContents($this->getS3Folder() . $fileName);

        $filepath = $this->getZipFolder() . $fileName;
        file_put_contents($filepath, $content);

        if($this->fs->exists($filepath))
        {
            $this->downloadedFileNames[] = $fileName;

        } else {
            $this->failedDownloads[] = $fileName;
        }
    }


    /**
     * @param $zipFileName
     * @return bool
     */
    private function unzipResultFiles($zipFileName)
    {
        $this->logger->notice('Unzipping file: '.$this->getZipFolder().$zipFileName);
        if ($this->zip->open($this->getZipFolder().$zipFileName) === TRUE) {

            if(self::ONLY_UNZIP_SOLANI_AND_RELANI) {
                $this->zip->extractTo($this->getResultsFolder(), [Filename::SOLANI, Filename::RELANI]);
            } else {
                $this->zip->extractTo($this->getResultsFolder());
            }
            
            $this->zip->close();
            return true;
        }
        return false;
    }
    
    
    private function parseSolaniFiles()
    {
        switch ($this->currentBreedType) {
            //TODO
        }
    }
    
    
    private function parseRelaniFiles()
    {
        switch ($this->currentBreedType) {
            //TODO
        }
    }


    private function processBreedValues()
    {
        switch ($this->currentBreedType) {
            //TODO
        }
    }


    /**
     * @param string $filepath
     */
    private function purgeFolder($filepath)
    {
        $this->logger->notice('Purging MixBlup '.basename($filepath).' folder in cache...');
        FilesystemUtil::purgeFolder($filepath, $this->fs, $this->logger);
    }

    private function purgeZipFolder() { $this->purgeFolder($this->getZipFolder()); }
    private function purgeResultsFolder() { $this->purgeFolder($this->getResultsFolder()); }

    /** @return string */
    private function getS3Folder() { return MixBlupSetting::S3_MIXBLUP_OUTPUT_DIRECTORY.$this->key.'/'; }
    /** @return string */
    public function getZipFolder() { return $this->workingFolder.'/'.MixBlupFolder::ZIPS.'/'; }
    /** @return string */
    public function getResultsFolder() { return $this->workingFolder.'/'.MixBlupFolder::RESULTS.'/'; }


    /**
     * @param string $relsolZipname
     * @return string
     */
    public static function getBreedTypeByRelSolZipName($relsolZipname)
    {


        switch ($relsolZipname) {
            case MixBlupInstructionFile::BIRTH_PROGRESS: return BreedValueType::BIRTH;
            case MixBlupInstructionFile::EXTERIOR_LEG_WORK: return BreedValueType::EXTERIOR_LEG_WORK;
            case MixBlupInstructionFile::EXTERIOR_MUSCULARITY: return BreedValueType::EXTERIOR_MUSCULARITY;
            case MixBlupInstructionFile::EXTERIOR_PROPORTION: return BreedValueType::EXTERIOR_PROPORTION;
            case MixBlupInstructionFile::EXTERIOR_SKULL: return BreedValueType::EXTERIOR_SKULL;
            case MixBlupInstructionFile::EXTERIOR_PROGRESS: return BreedValueType::EXTERIOR_PROGRESS;
            case MixBlupInstructionFile::EXTERIOR_TYPE: return BreedValueType::EXTERIOR_TYPE;
            case MixBlupInstructionFile::FERTILITY: return BreedValueType::FERTILITY;
            case MixBlupInstructionFile::FERTILITY_1: return BreedValueType::FERTILITY_1;
            case MixBlupInstructionFile::FERTILITY_2: return BreedValueType::FERTILITY_2;
            case MixBlupInstructionFile::FERTILITY_3: return BreedValueType::FERTILITY_3;
            case MixBlupInstructionFile::LAMB_MEAT: return BreedValueType::LAMB_MEAT;
            case MixBlupInstructionFile::TAIL_LENGTH: return BreedValueType::TAIL_LENGTH;
            case MixBlupInstructionFile::WORM_RESISTANCE: return BreedValueType::WORM;
        }
    }


    /**
     * @param $zipFileName
     * @return string
     */
    public static function getBreedValueTypeByRelSolZipName($zipFileName)
    {
        return rtrim($zipFileName,'RelSol.zip');
    }
}