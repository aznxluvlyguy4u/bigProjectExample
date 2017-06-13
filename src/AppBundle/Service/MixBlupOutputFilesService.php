<?php


namespace AppBundle\Service;


use AppBundle\Constant\Filename;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MixBlupAnalysis;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupParseInstruction;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CsvParser;
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

    const BATCH_SIZE = 10000;

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

    /** @var array */
    private $solani1;
    /** @var array */
    private $solani2;
    /** @var array */
    private $solani3;
    /** @var array */
    private $relani1;
    /** @var array */
    private $relani2;
    /** @var array */
    private $relani3;

    /** @var boolean */
    private $useSolani2;
    /** @var boolean */
    private $useSolani3;

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

        $this->resetSolaniAndRelaniArrays();

        $this->mixBlupProcesses = [];
        //TODO include actual processes
        $this->mixBlupProcesses[MixBlupAnalysis::BIRTH_PROGRESS] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::EXTERIOR_LEG_WORK] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::EXTERIOR_MUSCULARITY] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::EXTERIOR_PROGRESS] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::EXTERIOR_PROPORTION] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::EXTERIOR_SKULL] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::EXTERIOR_TYPE] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::FERTILITY_1] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::FERTILITY_2] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::FERTILITY_3] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::LAMB_MEAT] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::TAIL_LENGTH] = null;
        $this->mixBlupProcesses[MixBlupAnalysis::WORM_RESISTANCE] = null;
    }


    private function resetSolaniAndRelaniArrays()
    {
        $this->solani1 = [];
        $this->solani2 = [];
        $this->solani3 = [];
        $this->relani1 = [];
        $this->relani2 = [];
        $this->relani3 = [];
        $this->useSolani2 = false;
        $this->useSolani3 = false;
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

                        $this->resetSolaniAndRelaniArrays();

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
        $ssv = CsvParser::parseSpaceSeparatedFile($this->getResultsFolder(), Filename::SOLANI);

        $setting = MixBlupParseInstruction::get($this->currentBreedType);
        //NOTE! Atleast $animalIdRow and $solani
        $animalIdRow = ArrayUtil::get(JsonInputConstant::ANIMAL_ID, $setting);
        $solani1Row = ArrayUtil::get(JsonInputConstant::SOLANI_1, $setting);
        $solani2Row = ArrayUtil::get(JsonInputConstant::SOLANI_2, $setting);
        $solani3Row = ArrayUtil::get(JsonInputConstant::SOLANI_3, $setting);

        foreach ($ssv as $row) {

            $animalId = $row[$animalIdRow];

            $this->solani1[$animalId] = $row[$solani1Row];

            if(is_int($solani2Row)) {
                $this->solani2[$animalId] = $row[$solani2Row];
            }

            if(is_int($solani3Row)) {
                $this->solani3[$animalId] = $row[$solani3Row];
            }
        }
    }
    
    
    private function parseRelaniFiles()
    {
        $ssv = CsvParser::parseSpaceSeparatedFile($this->getResultsFolder(), Filename::RELANI);

        $setting = MixBlupParseInstruction::get($this->currentBreedType);
        //NOTE! Atleast $animalIdRow and $solani
        $animalIdRow = ArrayUtil::get(JsonInputConstant::ANIMAL_ID, $setting);
        $relani1Row = ArrayUtil::get(JsonInputConstant::RELANI_1, $setting);
        $relani2Row = ArrayUtil::get(JsonInputConstant::RELANI_2, $setting);
        $relani3Row = ArrayUtil::get(JsonInputConstant::RELANI_3, $setting);

        foreach ($ssv as $row) {

            $animalId = $row[$animalIdRow];

            $this->relani1[$animalId] = $row[$relani1Row];

            if(is_int($relani2Row)) {
                $this->relani2[$animalId] = $row[$relani2Row];
            }

            if(is_int($relani3Row)) {
                $this->relani3[$animalId] = $row[$relani3Row];
            }
        }
    }


    private function processBreedValues()
    {
        $animalIds = array_keys($this->solani1);

        $this->useSolani2 = count($this->solani2) > 0;
        $this->useSolani3 = count($this->solani3) > 0;

        $sqlBatchString = '';
        $counter = 0;
        $batchCounter = 0;
        foreach ($animalIds as $animalId) {
            $breedValueInsertString = $this->processBreedValue($animalId);
            if($breedValueInsertString != null) {
                $sqlBatchString = $sqlBatchString . $breedValueInsertString;
                $counter++;
                $batchCounter++;
            }

            if($counter%self::BATCH_SIZE == 0 && $counter != 0) {
                //TODO persist per batchsize
                $sqlBatchString = '';
                $batchCounter = 0;
            }
        }

        //TODO persist
    }


    /**
     * TODO Use values to calculate the breedValue for the specific animal and create an sql insert query for it.
     *
     * @param $animalId
     * @return string
     */
    private function processBreedValue($animalId)
    {
        $this->currentBreedType;
        $solani1 = ArrayUtil::get($animalId, $this->solani1);
        $relani1 = ArrayUtil::get($animalId, $this->relani1);

        if($this->useSolani2) {
            $solani2 = ArrayUtil::get($animalId, $this->solani2);
            $relani2 = ArrayUtil::get($animalId, $this->relani2);
        }

        if($this->useSolani3) {
            $solani3 = ArrayUtil::get($animalId, $this->solani3);
            $relani3 = ArrayUtil::get($animalId, $this->relani3);
        }

        return '';//TODO sqlBatchInsertString
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
     * @param $zipFileName
     * @return string
     */
    public static function getBreedValueTypeByRelSolZipName($zipFileName)
    {
        return rtrim($zipFileName,'RelSol.zip');
    }
}