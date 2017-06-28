<?php


namespace AppBundle\Service;


use AppBundle\Component\MixBlup\MixBlupInstructionFileBase;
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
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\LoggerUtil;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
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
    const TEST_WITH_DOWNLOADED_ZIPS = true;
    const PURGE_ZIP_FOLDER_AFTER_SUCCESSFUL_RUN = false;
    const DELETE_QUEUE_MESSAGE_AFTER_SUCCESSFUL_RUN = false;
    const ONLY_DOWNLOAD_SOLANI_AND_RELANI = false;
    const ONLY_UNZIP_SOLANI_AND_RELANI = true;

    const TEST_COLUMN_ALIGNMENT = true;

    const BATCH_SIZE = 10000;

    const ZERO_INDEXED_SOLANI_COLUMN = 3;
    const ZERO_INDEXED_RELANI_COLUMN = 3;

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
    private $relaniDirect;
    /** @var array */
    private $relaniIndirect;
    /** @var array */
    private $currentBreedValueExistsByAnimalIdForGenerationDate;
    /** @var boolean */
    private $relaniDirectAndIndirectExists;

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

    /** @var array */
    private $minReliabilityByBreedValueTypeDutchDescription;

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
            $this->minReliabilityByBreedValueTypeDutchDescription[$breedValueType->getNl()] = $breedValueType->getMinReliability();
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
        $this->relaniDirect = [];
        $this->relaniIndirect = [];

        $this->currentBreedValueExistsByAnimalIdForGenerationDate = [];

        if($this->key != null) {

            $sql = "SELECT b.id, animal_id, t.nl as dutch_breed_value_type
                    FROM breed_value b
                      INNER JOIN breed_value_type t ON b.type_id = t.id
                    WHERE generation_date = '".$this->key."'";
            $results = $this->conn->query($sql)->fetchAll();

            foreach ($results as $result) {
                $dutchBreedValueType = $result['dutch_breed_value_type'];
                $animalId = $result['animal_id'];
                //$breedValueId = $result['id'];
                $this->addToCurrentBreedValueExistsByAnimalIdForGenerationDate($dutchBreedValueType, $animalId);
            }
        }
    }


    /**
     * @param string $dutchBreedValueType
     * @param integer $animalId
     */
    private function addToCurrentBreedValueExistsByAnimalIdForGenerationDate($dutchBreedValueType, $animalId)
    {
        if(!key_exists($dutchBreedValueType, $this->currentBreedValueExistsByAnimalIdForGenerationDate)) {
            $this->currentBreedValueExistsByAnimalIdForGenerationDate[$dutchBreedValueType] = [];
        }

        $this->currentBreedValueExistsByAnimalIdForGenerationDate[$dutchBreedValueType][$animalId] = true;
    }


    public function run()
    {
        $this->processNextMessage();
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

                    $solaniExists = file_exists($this->getResultsFolder() . Filename::SOLANI);
                    $relaniExists = file_exists($this->getResultsFolder() . Filename::RELANI);
                    $this->relaniDirectAndIndirectExists =
                        file_exists($this->getResultsFolder() . Filename::RELANI_DIRECT) &&
                        file_exists($this->getResultsFolder() . Filename::RELANI_INDIRECT);
                    $successfulUnzip = $solaniExists && ($relaniExists || $this->relaniDirectAndIndirectExists);

                    if($successfulUnzip) {

                        $this->resetSearchArrays();

                        $this->parseSolaniFiles();

                        if($this->relaniDirectAndIndirectExists) {
                            $this->parseRelaniDirectFiles();
                            $this->parseRelaniIndirectFiles();
                            $this->processDirectBreedValues();
                            $this->processIndirectBreedValues();
                        } else {
                            $this->parseRelaniFiles();
                            $this->processBreedValues();
                        }

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
                    if(self::DELETE_QUEUE_MESSAGE_AFTER_SUCCESSFUL_RUN) {
                        $this->queueService->deleteMessage($response);
                    }

                } else {
                    $this->logger->error('The following breedValues had no relani nor solani file: '.implode(', ', $blankBreedValueTypes));
                    $this->logger->error('The following unzips failed (relani or solani file likely missing): '.implode(', ', $unsuccessfulUnzips));
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

        $this->totalFilesToDownload = self::ONLY_DOWNLOAD_SOLANI_AND_RELANI ? count($this->relsol) : count($this->bulkFiles) + count($this->relsol);

        if(!self::ONLY_DOWNLOAD_SOLANI_AND_RELANI) {
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
                /*
                 * For some reason the Relani_direct and Relani_indirect filenames
                 * cannot be in the same array as Solani and Relani,
                 * or else the zip extraction won't work properly.
                 */
                $this->zip->extractTo($this->getResultsFolder(), [Filename::RELANI_DIRECT, Filename::RELANI_INDIRECT]);
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

        if(count($ssv) === 0) {
            $this->logger->notice(Filename::SOLANI . ' is empty');
            return;
        }

        $dutchBreedValueTypes = MixBlupParseInstruction::get($this->currentBreedType, true);
        if(count($dutchBreedValueTypes) === 0) {
            $this->logger->error($this->currentBreedType . ' extracted currentBreedType is not a valid MixBlupAnalysis type');
            return;
        }

        $firstRow = $ssv[0];
        $firstColumnIndex = $this->getFirstColumnIndex($ssv[0]);
        if($firstColumnIndex === null) {
            $this->logger->error($this->currentBreedType . ' first record is blank');
            return;
        }

        if(self::TEST_COLUMN_ALIGNMENT) {
            dump([  'dutch_breed_value_types' => $dutchBreedValueTypes,
                'first_row' => $firstRow,
                'first_column_index' => $firstColumnIndex
            ]);
        }

        $recordsStoredCount = 0;
        $recordsSkippedCount = 0;
        $foundValue = false;
        foreach ($ssv as $row) {

            $animalId = $row[$firstColumnIndex];
            foreach ($dutchBreedValueTypes as $ordinal => $dutchBreedValueType) {
                $solaniBreedValueGroup = ArrayUtil::get($dutchBreedValueType, $this->solani, []);

                //0-indexed solani column n starts at 0-indexed $ssv row[n+3] / column 4 in the file
                $key = $ordinal+self::ZERO_INDEXED_SOLANI_COLUMN+$firstColumnIndex;
                $value = $row[$key];
                if($value !== null && $value !== '') {
                    $floatValue = floatval($value);
                    //NOTE! Zero and negative Solani values are valid!
                    $solaniBreedValueGroup[$animalId] = $floatValue;
                    $this->solani[$dutchBreedValueType] = $solaniBreedValueGroup;
                    $recordsStoredCount++;

                    if(self::TEST_COLUMN_ALIGNMENT) {
                        dump([$key => $value]);
                        $foundValue = true;
                    }
                } else {
                    $recordsSkippedCount++;
                }
            }
            if(self::TEST_COLUMN_ALIGNMENT && $foundValue) { break; }
        }
        if(self::TEST_COLUMN_ALIGNMENT) { dump($this->solani); }

        $this->logger->notice('Solani records stored|skipped: '.$recordsStoredCount.'|'.$recordsSkippedCount);
    }


    /**
     * NOTE! Null Relani values are already filtered out. So the Relani_direct array will be smaller than the Solani array!
     *
     * Dynamically fill the relaniDirect search array
     */
    private function parseRelaniDirectFiles()
    {
        $this->parseRelaniFiles(Filename::RELANI_DIRECT);
    }


    /**
     * NOTE! Null Relani values are already filtered out. So the Relani_indirect array will be smaller than the Solani array!
     *
     * Dynamically fill the relaniIndirect search array
     */
    private function parseRelaniIndirectFiles()
    {
        $indirectDutchBreedValueTypes = MixBlupParseInstruction::getIndirect($this->currentBreedType, false);
        $this->parseRelaniFiles(Filename::RELANI_INDIRECT, $indirectDutchBreedValueTypes);
    }


    /**
     * NOTE! Null Relani values are already filtered out. So the Relani array will be smaller than the Solani array!
     *
     * Dynamically fill the relani search array
     * @param $filename
     * @param $indirectDutchBreedValueTypes
     */
    private function parseRelaniFiles($filename = Filename::RELANI, $indirectDutchBreedValueTypes = [])
    {
        $ssv = CsvParser::parseSpaceSeparatedFile($this->getResultsFolder(), $filename);

        if(count($ssv) === 0) {
            $this->logger->notice($filename . ' is empty');
            return;
        }

        $dutchBreedValueTypes = $filename === Filename::RELANI_INDIRECT ? MixBlupParseInstruction::getIndirect($this->currentBreedType) :
            MixBlupParseInstruction::get($this->currentBreedType, false);
        if(count($dutchBreedValueTypes) === 0) {
            $this->logger->error($this->currentBreedType . ' extracted currentBreedType is not a valid MixBlupAnalysis type');
            return;
        }

        $recordsStoredCount = 0;
        $recordsSkippedCount = 0;
        $foundValue = false;

        $firstRow = $ssv[0];
        $firstColumnIndex = $this->getFirstColumnIndex($ssv[0]);
        if($firstColumnIndex === null) {
            $this->logger->error($this->currentBreedType . ' first record is blank');
            return;
        }

        if(self::TEST_COLUMN_ALIGNMENT) {
            dump([  'dutch_breed_value_types' => $dutchBreedValueTypes,
                'first_row' => $firstRow,
                'first_column_index' => $firstColumnIndex
            ]);
        }

        foreach ($ssv as $row) {

            $animalId = $row[$firstColumnIndex];
            foreach ($dutchBreedValueTypes as $ordinal => $dutchBreedValueType) {
                switch ($filename)
                {
                    case Filename::RELANI_DIRECT:
                        $relaniBreedValueGroup = ArrayUtil::get($dutchBreedValueType, $this->relaniDirect, []);
                        break;

                    case Filename::RELANI_INDIRECT:
                        $relaniBreedValueGroup = ArrayUtil::get($dutchBreedValueType, $this->relaniIndirect, []);
                        break;

                    default:
                        $relaniBreedValueGroup = ArrayUtil::get($dutchBreedValueType, $this->relani, []);
                        break;
                }

                //0-indexed relani column n starts at 0-indexed $ssv row[n+3] / column 4 in the file
                $key = $ordinal+self::ZERO_INDEXED_RELANI_COLUMN+$firstColumnIndex;
                $value = $row[$key];
                if($value !== null && $value !== '') {
                    $floatValue = floatval($value);

                    if(!NumberUtil::isFloatZero($floatValue, MixBlupSetting::FLOAT_ACCURACY)) {
                        $relaniBreedValueGroup[$animalId] = $floatValue;
                        switch ($filename)
                        {
                            case Filename::RELANI_DIRECT:
                                $this->relaniDirect[$dutchBreedValueType] = $relaniBreedValueGroup;
                                break;

                            case Filename::RELANI_INDIRECT:
                                $this->relaniIndirect[$dutchBreedValueType] = $relaniBreedValueGroup;
                                break;

                            default:
                                $this->relani[$dutchBreedValueType] = $relaniBreedValueGroup;
                                break;
                        }
                        $recordsStoredCount++;

                        if(self::TEST_COLUMN_ALIGNMENT) {
                            dump([
                                'first_non_zero...' => 'key => value',
                                $key => $value
                            ]);
                            $foundValue = true;
                        }
                    } else {
                        $recordsSkippedCount++;
                    }
                } else {
                    $recordsSkippedCount++;
                }
            }
            if(self::TEST_COLUMN_ALIGNMENT && $foundValue) { break; }
        }
        if(self::TEST_COLUMN_ALIGNMENT) {
            switch ($filename)
            {
                case Filename::RELANI_DIRECT: dump($this->relaniDirect); break;
                case Filename::RELANI_INDIRECT: dump($this->relaniIndirect); break;
                default: dump($this->relani); break;
            }
        }

        $this->logger->notice($filename . ' records stored|skipped: '.$recordsStoredCount.'|'.$recordsSkippedCount);
    }


    /**
     * @param array $row
     * @return int|null|string
     */
    private function getFirstColumnIndex($row)
    {
        foreach ($row as $key => $value) {
            if($value !== '') {
                return $key;
            }
        }
        return null;
    }


    /**
     * Check if the breedValue already exists for the given dutchBreedValueType and generationDateString = $this->key.
     *
     * @param string $dutchBreedValueType
     * @param int $animalId
     * @return bool
     */
    private function breedValueAlreadyExists($dutchBreedValueType, $animalId)
    {
        //
        $dutchBreedValueTypeGroup = ArrayUtil::get($dutchBreedValueType, $this->currentBreedValueExistsByAnimalIdForGenerationDate);
        if($dutchBreedValueTypeGroup != null) {
            $animalId = ArrayUtil::get($animalId, $dutchBreedValueTypeGroup);
            return key_exists($animalId, $dutchBreedValueTypeGroup);
        }
        return false;
    }


    private function processBreedValues()
    {
        $this->processBreedValuesBase($this->relani);
    }


    private function processDirectBreedValues()
    {
        $this->processBreedValuesBase($this->relaniDirect);
    }


    private function processIndirectBreedValues()
    {
        $this->processBreedValuesBase($this->relaniIndirect, true);
    }


    private function processBreedValuesBase($relani, $hasIndirectSuffix = false)
    {
        $sqlBatchString = '';
        $prefix = '';
        $totalCount = 0;
        $batchCount = 0;
        $valueAlreadyExistsCount = 0;
        $nullAccuracyCount = 0;

        DoctrineUtil::updateTableSequence($this->conn, [BreedValue::TABLE_NAME]);

        foreach ($relani as $dutchBreedValueTypeKeyInSolaniArray => $relaniValues) {
            $dutchBreedValueTypeForDatabase = $dutchBreedValueTypeKeyInSolaniArray;
            if($hasIndirectSuffix) {
                $dutchBreedValueTypeForDatabase = $this->removeIndirectSuffix($dutchBreedValueTypeKeyInSolaniArray);
            }

            $this->logger->notice('Processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues ...');

            $breedValueTypeId = $this->breedValueTypeIdsByDutchDescription[$dutchBreedValueTypeForDatabase];

            //NOTE! Null Relani values are already filtered out. So the Relani array will be smaller than the Solani array!
            foreach ($relaniValues as $animalId => $relani) {

                if($this->breedValueAlreadyExists($dutchBreedValueTypeForDatabase, $animalId)) {
                    $valueAlreadyExistsCount++;
                    continue;
                }

                //Note! a 0.000 value in the relani file refers to a null/missing value.
                //Do not persist these in the database.
                if(NumberUtil::isFloatZero($relani, MixBlupSetting::FLOAT_ACCURACY)) {
                    $nullAccuracyCount++;
                    continue;
                }

                $solaniValues = ArrayUtil::get($dutchBreedValueTypeKeyInSolaniArray, $this->solani);
                $solani = $solaniValues != null ? ArrayUtil::get($animalId, $solaniValues) : null;

                if($solani != null) {

                    $breedValueInsertString = $this->writeBreedValueInsertValuesString($prefix, $breedValueTypeId, $animalId, $solani, $relani);

                    $sqlBatchString = $sqlBatchString . $breedValueInsertString;
                    $totalCount++;
                    $batchCount++;
                    $prefix = ',';

                    $this->addToCurrentBreedValueExistsByAnimalIdForGenerationDate($dutchBreedValueTypeForDatabase, $animalId);

                    if($totalCount%self::BATCH_SIZE == 0 && $totalCount != 0) {
                        $this->persistBreedValueBySql($sqlBatchString);
                        $sqlBatchString = '';
                        $prefix = '';
                        $batchCount = 0;
                    }
                }

                $message = 'Processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues count total|batch: '.$totalCount.'|'.$batchCount;
                $this->overwriteNotice($message);

            }

            if($sqlBatchString != '') {
                $this->persistBreedValueBySql($sqlBatchString);
                $sqlBatchString = '';
                $prefix = '';
                $batchCount = 0;

                $message = 'Processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues count total|batch: '.$totalCount.'|'.$batchCount;
                $this->overwriteNotice($message);
            }
            $this->logger->notice('Finished processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues.');
        }

        $this->logger->notice('Finished processing breedvalues set!');
    }


    /**
     * @param string $prefix
     * @param int $breedValueTypeId
     * @param int $animalId
     * @param float $solani
     * @param float $relani
     * @return string
     */
    private function writeBreedValueInsertValuesString($prefix, $breedValueTypeId, $animalId, $solani, $relani)
    {
        return $prefix."(nextval('breed_value_id_seq'),".$animalId.",".$breedValueTypeId.",NOW(),'".$this->key."','". $solani."','".$relani."')";
    }


    /**
     * @param string $sqlBatchString
     * @return integer
     */
    private function persistBreedValueBySql($sqlBatchString)
    {
        if($sqlBatchString == '') { return 0; }

        $sql = "INSERT INTO breed_value (id, animal_id, type_id, log_date, generation_date, value, reliability) VALUES ".$sqlBatchString;
        $this->conn->exec($sql);

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
     * Removes RelSol.zip if it exists in the string.
     *
     * @param $zipFileName
     * @return string
     */
    public static function getBreedValueTypeByRelSolZipName($zipFileName)
    {
        return StringUtil::removeSuffix($zipFileName, 'RelSol.zip');
    }


    /**
     * @param $breedValueType
     * @return string
     */
    private function removeIndirectSuffix($breedValueType)
    {
        return StringUtil::removeSuffix($breedValueType, MixBlupInstructionFileBase::INDIRECT_SUFFIX);
    }


    /**
     * @param $line
     */
    private function overwriteNotice($line)
    {
        LoggerUtil::overwriteNotice($this->logger, $line);
    }
}