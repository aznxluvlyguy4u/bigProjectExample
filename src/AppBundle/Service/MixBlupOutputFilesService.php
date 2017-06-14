<?php


namespace AppBundle\Service;


use AppBundle\Constant\Filename;
use AppBundle\Entity\BreedIndexType;
use AppBundle\Entity\BreedIndexTypeRepository;
use AppBundle\Entity\BreedValue;
use AppBundle\Entity\BreedValueRepository;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\BreedValueTypeRepository;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupParseInstruction;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CsvParser;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\SqlUtil;
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

    /** @var string */
    private $key;
    /** @var array */
    private $bulkFiles;
    /** @var array */
    private $relsol;
    /** @var array */
    private $errors;

    /** @var array */
    private $solani;
    /** @var array */
    private $relani;
    /** @var array */
    private $currentBreedValueIdsByAnimalIdForGenerationDate;

    /** @var int */
    private $totalFilesToDownload;
    /** @var array */
    private $downloadedFileNames;
    /** @var array */
    private $failedDownloads;
    /** @var string */
    private $currentBreedType;

    /** @var BreedIndexTypeRepository */
    private $breedIndexTypeRepository;
    /** @var BreedValueTypeRepository */
    private $breedValueTypeRepository;
    /** @var BreedValueRepository */
    private $breedValueRepository;

    /** @var array */
    private $breedValueTypesByDutchDescription;
    /** @var array */
    private $breedIndexTypesByDutchDescription;
    /** @var array */
    private $breedValueTypeIdsByDutchDescription;
    /** @var array */
    private $breedIndexTypeIdsByDutchDescription;

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

        $this->breedIndexTypeRepository = $this->em->getRepository(BreedIndexType::class);
        $this->breedValueTypeRepository = $this->em->getRepository(BreedValueType::class);
        $this->breedValueRepository = $this->em->getRepository(BreedValue::class);

        $this->workingFolder = $cacheDir.'/'.MixBlupFolder::ROOT;
        FilesystemUtil::createFolderPathIfNull([$this->getZipFolder(), $this->getResultsFolder()]);

        $this->fs = new Filesystem();
        $this->zip = new \ZipArchive();

        $this->resetSearchArrays();
        $this->setSearchArrays();
    }



    private function setSearchArrays()
    {
        $breedValueTypes = $this->breedValueTypeRepository->findAll();
        /** @var BreedValueType $breedValueType */
        foreach ($breedValueTypes as $breedValueType) {
            $this->breedValueTypesByDutchDescription[$breedValueType->getNl()] = $breedValueType;
            $this->breedValueTypeIdsByDutchDescription[$breedValueType->getNl()] = $breedValueType->getId();
        }

        $breedIndexTypes = $this->breedIndexTypeRepository->findAll();
        /** @var BreedIndexType $breedIndexType */
        foreach ($breedIndexTypes as $breedIndexType) {
            $this->breedIndexTypesByDutchDescription[$breedValueType->getNl()] = $breedValueType;
            $this->breedIndexTypeIdsByDutchDescription[$breedValueType->getNl()] = $breedValueType->getId();
        }
    }


    private function resetSearchArrays()
    {
        $this->solani = [];
        $this->relani = [];

        $this->currentBreedValueIdsByAnimalIdForGenerationDate = [];

        if($this->key != null) {

            $sql = "SELECT b.id, animal_id, t.nl as dutch_breed_value_type
                    FROM breed_value b
                      INNER JOIN breed_value_type t ON b.type_id = t.id
                    WHERE generation_date = '".$this->key."'";
            $results = $this->conn->query($sql)->fetchAll();

            foreach ($results as $result) {
                $dutchBreedValueType = $result['dutch_breed_value_type'];
                $animalId = $result['animal_id'];
                $breedValueId = $result['id'];

                if(!key_exists($dutchBreedValueType, $this->currentBreedValueIdsByAnimalIdForGenerationDate)) {
                    $this->currentBreedValueIdsByAnimalIdForGenerationDate[$dutchBreedValueType] = [];
                }

                $this->currentBreedValueIdsByAnimalIdForGenerationDate[$dutchBreedValueType][$animalId] = $breedValueId;
            }
        }
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

                        $this->resetSearchArrays();

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


    /**
     * Dynamically fill the solani search array
     */
    private function parseSolaniFiles()
    {
        $ssv = CsvParser::parseSpaceSeparatedFile($this->getResultsFolder(), Filename::SOLANI);

        $dutchBreedValueTypes = MixBlupParseInstruction::get($this->currentBreedType);

        foreach ($ssv as $row) {

            $animalId = $row[0];
            foreach ($dutchBreedValueTypes as $ordinal => $dutchBreedValueType) {
                $solaniBreedValue = ArrayUtil::get($dutchBreedValueType, $this->solani, []);

                //0-indexed solani column n starts at 0-indexed $ssv row[n+3] / column 4 in the file
                $solaniBreedValue[$animalId] = $row[$ordinal+3];
                $this->solani[$dutchBreedValueType] = $solaniBreedValue;
            }

        }
    }


    /**
     * Dynamically fill the relani search array
     */
    private function parseRelaniFiles()
    {
        $ssv = CsvParser::parseSpaceSeparatedFile($this->getResultsFolder(), Filename::RELANI);

        $dutchBreedValueTypes = MixBlupParseInstruction::get($this->currentBreedType);

        foreach ($ssv as $row) {

            $animalId = $row[0];
            foreach ($dutchBreedValueTypes as $ordinal => $dutchBreedValueType) {
                $relaniBreedValue = ArrayUtil::get($dutchBreedValueType, $this->relani, []);

                //0-indexed relani column n starts at 0-indexed $ssv row[n+3] / column 4 in the file
                $relaniBreedValue[$animalId] = $row[$ordinal+3];
                $this->relani[$dutchBreedValueType] = $relaniBreedValue;
            }
        }
    }


    private function processBreedValues()
    {
        $sqlBatchString = '';
        $prefix = '';
        $totalCount = 0;
        $batchCount = 0;
        $valueAlreadyExistsCount = 0;
        $nullAccuracyCount = 0;

        //TODO add a $cmdUtil counter
        foreach ($this->relani as $dutchBreedValueType => $relaniValues) {

            $breedValueId = $this->breedValueTypeIdsByDutchDescription[$dutchBreedValueType];

            foreach ($relaniValues as $animalId => $relaniValue) {

                //TODO check if the breedValue already exists for the given generationDate and dutchBreedValueType
                $alreadyExists = $this->currentBreedValueIdsByAnimalIdForGenerationDate[$dutchBreedValueType][$animalId];
                if($alreadyExists) {
                    $valueAlreadyExistsCount++;
                    continue;
                }

                //Note! a 0.000 value in the relani file refers to a null/missing value.
                //Do not persist these in the database.
                //TODO verify if floatval actually works and if the value type correct
                $relani = floatval($relaniValue);
                if(NumberUtil::isFloatZero($relani)) {
                    $nullAccuracyCount++;
                    continue;
                }

                $solaniValues = ArrayUtil::get($dutchBreedValueType, $this->solani);
                $solaniValue = ArrayUtil::get($animalId, $solaniValues);

                if($solaniValue != null) {

                    $solani = floatval($solaniValue);
                    //TODO add $breedValueId,  animalId, solani, relani to sqlInsertString
                    $breedValueInsertString = $this->writeBreedValueInsertString($prefix, $breedValueId, $animalId, $solani, $relani); //TODO

                    $sqlBatchString = $sqlBatchString . $breedValueInsertString;
                    $totalCount++;
                    $batchCount++;
                    $prefix = ',';

                    if($totalCount%self::BATCH_SIZE == 0 && $totalCount != 0) {
                        $this->persistBreedValueBySql($sqlBatchString); //TODO
                        $sqlBatchString = '';
                        $prefix = '';
                        $batchCount = 0;
                    }
                }

            }
        }

        if($sqlBatchString != '') {
            $this->persistBreedValueBySql($sqlBatchString); //TODO
            $sqlBatchString = '';
            $prefix = '';
            $batchCount = 0;
        }
    }


    /**
     * @param string $prefix
     * @param int $breedValueId
     * @param int $animalId
     * @param float $solani
     * @param float $relani
     * @return string
     */
    private function writeBreedValueInsertString($prefix, $breedValueId, $animalId, $solani, $relani)
    {
        return ''; //TODO
    }


    /**
     * TODO
     *
     * @param string $sqlBatchString
     * @return integer
     */
    private function persistBreedValueBySql($sqlBatchString)
    {
        if($sqlBatchString == '') { return 0; }
        $sql = '';
        return SqlUtil::updateWithCount($this->conn, $sql);
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
     * @return \DateTime|null
     */
    private function getDateTimeFromKey()
    {
        if($this->key != null) {
            return new \DateTime(strtr($this->key, ['_' => ' ']));
        }
        return null;
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