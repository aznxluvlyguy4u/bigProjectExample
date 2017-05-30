<?php


namespace AppBundle\Service;


use AppBundle\Enumerator\MixBlupType;
use AppBundle\Setting\MixBlupFolder;
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
    private $files;

    /** @var int */
    private $totalFilesToDownload;
    /** @var array */
    private $downloadedFileNames;
    /** @var array */
    private $failedDownloads;

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
//        $this->mixBlupProcesses[MixBlupType::EXTERIOR] = new ExteriorProcess($em, $this->workingFolder);
//        $this->mixBlupProcesses[MixBlupType::LAMB_MEAT_INDEX] = new LambMeatIndexProcess($em, $this->workingFolder);
//        $this->mixBlupProcesses[MixBlupType::FERTILITY] = new ReproductionProcess($em, $this->workingFolder);
    }


    public function run()
    {
        // TODO: Implement run() method.
        $this->processNextMessage();
    }


    private function processNextMessage()
    {
        $response = $this->queueService->getNextMessage();
        $messageBody = AwsQueueServiceBase::getMessageBodyFromResponse($response);
        if ($messageBody) {
            $this->key = $messageBody->key;
            $this->files = $messageBody->files;

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

                foreach($this->files as $zipFileName){

                    $this->purgeResultsFolder();
                    $successfulUnzip = $this->unzipResultFiles($zipFileName);

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
                    }
                }

                if(count($unsuccessfulUnzips) == 0) {
                    $this->logger->notice('All breedValues processed successfully!');

                    if(!self::TEST_WITH_DOWNLOADED_ZIPS) {
                        $this->queueService->deleteMessage($response);
                        $this->purgeZipFolder();
                    }

                } else {
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
        $this->totalFilesToDownload = count($this->files);
        $this->downloadedFileNames = [];
        $this->failedDownloads = [];

        // download all files
        foreach($this->files as $file){
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
            //TODO only unzip relevant files
            $this->zip->extractTo($this->getResultsFolder());
            $this->zip->close();
            return true;
        }
        return false;
    }
    
    
    private function parseSolaniFiles()
    {
        //TODO
    }
    
    
    private function parseRelaniFiles()
    {
        //TODO
    }


    private function processBreedValues()
    {
        //TODO
    }


    /**
     * @param string $filepath
     */
    private function purgeFolder($filepath)
    {
        $this->logger->notice('Purging MixBlup '.basename($filepath).' folder in cache...');
        FilesystemUtil::recurseRemove($filepath, $this->fs, $this->logger);
    }

    private function purgeZipFolder() { $this->purgeFolder($this->getZipFolder()); }
    private function purgeResultsFolder() { $this->purgeFolder($this->getResultsFolder()); }

    /** @return string */
    private function getS3Folder() { return MixBlupSetting::S3_MIXBLUP_OUTPUT_DIRECTORY.$this->key.'/'; }
    /** @return string */
    public function getZipFolder() { return $this->workingFolder.'/'.MixBlupFolder::ZIPS.'/'; }
    /** @return string */
    public function getResultsFolder() { return $this->workingFolder.'/'.MixBlupFolder::RESULTS.'/'; }
}