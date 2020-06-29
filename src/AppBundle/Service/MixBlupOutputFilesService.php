<?php


namespace AppBundle\Service;


use AppBundle\Cache\BreedValuesResultTableUpdater;
use AppBundle\Component\MixBlup\MixBlupInstructionFileBase;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Constant\Filename;
use AppBundle\Constant\MixBlupAnalysis;
use AppBundle\Entity\BreedIndexType;
use AppBundle\Entity\BreedIndexTypeRepository;
use AppBundle\Entity\BreedValue;
use AppBundle\Entity\BreedValueRepository;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\BreedValueTypeRepository;
use AppBundle\Enumerator\MixBlupType;
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
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
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
    const PURGE_ZIP_FOLDER_AFTER_SUCCESSFUL_RUN = true;
    const DELETE_QUEUE_MESSAGE_AFTER_SUCCESSFUL_RUN = true;
    const ONLY_DOWNLOAD_SOLANI_AND_RELANI = true;
    const ONLY_UNZIP_SOLANI_AND_RELANI = true;

    const TEST_COLUMN_ALIGNMENT = false;

    const BATCH_SIZE = 10000;
    const PRINT_BATCH_SIZE = 1000;

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
    /** @var BreedIndexService */
    private $breedIndexService;
    /** @var BreedValueService */
    private $breedValueService;
    /** @var NormalDistributionService */
    private $normalDistributionService;
    /** @var Logger */
    private $logger;
    /** @var BreedValuesResultTableUpdater */
    private $breedValuesResultTableUpdater;

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
    private $animalIdsInDatabase;

    /** @var array */
    private $minReliabilityByBreedValueTypeDutchDescription;



    /** @var array */
    private $sqlBatchSets;
    /** @var int */
    private $persistenceSetErrorsCount;
    /** @var int */
    private $totalSavedCount;
    /** @var int */
    private $toSaveBatchCount;
    /** @var int */
    private $valueAlreadyExistsCount;
    /** @var int */
    private $nullAccuracyCount;
    /** @var boolean */
    private $processScanCount;
    /** @var array */
    private $missingAnimalIds;
    /** @var array */
    private $validatedAnimalIds;
    /** @var array */
    private $animalIdsInOutputFile;
    /** @var array */
    private $hasFilenameArray;

    public function __construct(ObjectManager $em, AWSSimpleStorageService $s3Service, MixBlupOutputQueueService $queueService,
                                BreedIndexService $breedIndexService, BreedValueService $breedValueService,
                                BreedValuesResultTableUpdater $breedValuesResultTableUpdater,
                                NormalDistributionService $normalDistributionService,
                                $currentEnvironment, $cacheDir, $logger = null)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->s3Service = $s3Service;
        $this->queueService = $queueService;
        $this->breedIndexService = $breedIndexService;
        $this->breedValueService = $breedValueService;
        $this->normalDistributionService = $normalDistributionService;
        $this->currentEnvironment = $currentEnvironment;
        $this->cacheDir = $cacheDir;
        $this->logger = $logger;

        $this->breedValuesResultTableUpdater = $breedValuesResultTableUpdater;

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
            $this->breedIndexTypesByDutchDescription[$breedIndexType->getNl()] = $breedIndexType;
            $this->breedIndexTypeIdsByDutchDescription[$breedIndexType->getNl()] = $breedIndexType->getId();
        }

        $this->missingAnimalIds = [];

        $sql = "SELECT id FROM animal";
        $results = $this->conn->query($sql)->fetchAll();
        $this->animalIdsInDatabase = SqlUtil::getSingleValueGroupedSqlResults('id', $results, true, true);
    }


    private function resetSearchArrays()
    {
        $this->solani = [];
        $this->relani = [];
        $this->relaniDirect = [];
        $this->relaniIndirect = [];
        $this->hasFilenameArray = [];

        $this->currentBreedValueExistsByAnimalIdForGenerationDate = [];

        if($this->key != null) {

            $breedTypeFilterString = "";
            if (!empty($this->currentBreedType)) {
                $breedTypeArrayString = "'".
                    implode("','",MixBlupSetting::breedTypeByAnalysis($this->currentBreedType))
                    ."'";
                $breedTypeFilterString = " AND t.nl IN ($breedTypeArrayString)";
                $this->logger->notice("Retrieving breedValues of types $breedTypeArrayString for search array");
            }

            $sql = "SELECT b.id, animal_id, t.nl as dutch_breed_value_type
                    FROM breed_value b
                      INNER JOIN breed_value_type t ON b.type_id = t.id
                    WHERE generation_date = '".$this->key."'".$breedTypeFilterString;
            $results = $this->conn->query($sql)->fetchAll();

            foreach ($results as $result) {
                $dutchBreedValueType = $result['dutch_breed_value_type'];
                $animalId = $result['animal_id'];
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
        do {

            $newMessageProcessed =  $this->processNextMessage();

            // wait a while before processing next possible message
            sleep(5);

            // continue until all messages are processed
        } while ($newMessageProcessed);
    }


    /**
     * @return bool
     */
    private function processNextMessage()
    {
        $response = $this->queueService->getNextMessage();
        $messageBody = AwsQueueServiceBase::getMessageBodyFromResponse($response);
        if ($messageBody) {
            $this->key = $messageBody->key; // NOTE this should be the generationDateString with underscore between date and time
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
                $hasBothRelaniTypeFilesInZip = [];

                foreach($this->relsol as $zipFileName){

                    $this->purgeResultsFolder();
                    $this->unzipResultFiles($zipFileName);
                    $this->currentBreedType = self::getBreedValueTypeByRelSolZipName($zipFileName);

                    $solaniExists = file_exists($this->getResultsFolder() . Filename::SOLANI);
                    $relaniExists = file_exists($this->getResultsFolder() . Filename::RELANI);
                    $this->relaniDirectAndIndirectExists =
                        file_exists($this->getResultsFolder() . Filename::RELANI_DIRECT) &&
                        file_exists($this->getResultsFolder() . Filename::RELANI_INDIRECT);


                    if ($relaniExists && $this->relaniDirectAndIndirectExists) {
                        $this->logger->error($this->currentBreedType. ' has normal and (in)direct Relani files.
                        Thus something when wrong during the MiXBLUP output files processing');
                        $this->errors[] = $this->currentBreedType;
                        $hasBothRelaniTypeFilesInZip[] = $this->currentBreedType;
                    } else {
                        $successfulUnzip = $solaniExists && ($relaniExists || $this->relaniDirectAndIndirectExists);

                        if($successfulUnzip) {
                            $this->logger->notice('Unzip was successful!');

                            $this->resetSearchArrays();

                            $this->runBreedValueTypeCustomPreparationLogic();

                            $this->parseSolaniFiles();

                            if($this->relaniDirectAndIndirectExists) {
                                $this->logger->notice('Found separate direct and indirect Relani files');
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
                            $this->logger->notice('Unzip failed');
                            $unsuccessfulUnzips[] = $zipFileName;
                            $this->errors[] = $this->currentBreedType;
                        }
                    }
                }


                if ($this->persistenceSetErrorsCount > 0) {
                    $this->logger->error('WARNING THERE HAVE BEEN '
                        .$this->persistenceSetErrorsCount
                        . ' PERSISTENCE ERRORS!');
                }


                $this->removeDuplicateBreedValues();

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

                $missingAnimalIdsCount = count($this->missingAnimalIds);
                if($missingAnimalIdsCount > 0) {
                    $this->logger->error($missingAnimalIdsCount.' AnimalIds missing from database! : '.implode(',', $this->missingAnimalIds));
                } else {
                    $this->logger->notice('No animalIds were missing in the database');
                }

                $this->updateResultTableBreedValuesAndTheirPrerequisites();

                return true;

            } else {
                // Handle unsuccessful download
                $this->logger->error('Download of files from s3 bucket unsuccessful for key: '.$this->key);
                $this->logger->error('Download failed for these files: '.implode(', ', $this->failedDownloads));
                $this->logger->notice('Download succeeded for these files: '.implode(', ', $this->downloadedFileNames));
            }

        } else {
            $this->logger->notice('There is currently no message in the queue');
        }

        return false;
    }


    private function runBreedValueTypeCustomPreparationLogic()
    {
        if ($this->currentBreedType == MixBlupAnalysis::TAIL_LENGTH) {
            $this->printRunCustomPreparationLogicHeader();
            $this->extractAnimalIdsFromSolaniFiles();

            $sql = "SELECT id, breed_code FROM animal 
                    WHERE breed_code LIKE '%CF%' -- any breedCode containing CF
                      AND id IN (".implode(',',$this->animalIdsInOutputFile).")";
            $res = $this->conn->query($sql)->fetchAll();
            $this->validatedAnimalIds = SqlUtil::getSingleValueGroupedSqlResults('id',$res, true,false);
        }
    }


    private function printRunCustomPreparationLogicHeader()
    {
        $this->logger->notice("=== Running CUSTOM ".$this->currentBreedType." preparation logic ===");
    }


    private function extractAnimalIdsFromSolaniFiles()
    {
        $ssv = CsvParser::parseSpaceSeparatedFile($this->getResultsFolder(), Filename::SOLANI);

        $totalRowCount = count($ssv);
        if($totalRowCount === 0) {
            $this->logger->notice(Filename::SOLANI . ' is empty');
            return;
        }

        $dutchBreedValueTypes = MixBlupParseInstruction::get($this->currentBreedType, true);
        if(count($dutchBreedValueTypes) === 0) {
            $this->logger->error($this->currentBreedType . ' extracted currentBreedType is not a valid MixBlupAnalysis type');
            return;
        }

        $firstColumnIndex = $this->getFirstColumnIndex($ssv[0]);
        if($firstColumnIndex === null) {
            $this->logger->error($this->currentBreedType . ' first record is blank');
            return;
        }

        $this->logger->notice('Extracting animalIds from '.Filename::SOLANI.' file for '.$this->currentBreedType. ' ... ');
        $this->logger->notice(' ... '); //Line to overwrite
        $this->logger->notice(' ... '); //Line to overwrite

        $rowCount = 0;
        $this->animalIdsInOutputFile = [];

        foreach ($ssv as $row) {
            $animalId = $row[$firstColumnIndex];
            $this->animalIdsInOutputFile[$animalId] = $animalId;
            $rowCount++;
            if($rowCount%self::PRINT_BATCH_SIZE === 0) {
                $this->overwriteNotice('animalId records scanned: '.$rowCount);
            }
        }
        $this->logger->notice('Total animalIds found: '.count($this->animalIdsInOutputFile));
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

            $extractedFiles = array_diff(scandir($this->getResultsFolder()), array('.', '..'));
            $this->logger->notice("Extracted files:");
            foreach($extractedFiles as $extractedFile) {
                $this->logger->notice($extractedFile);
            }

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

        $totalRowCount = count($ssv);
        if($totalRowCount === 0) {
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

        $this->logger->notice('Parsing '.Filename::SOLANI.' file for '.$this->currentBreedType. ' ... ');
        $this->logger->notice(' ... '); //Line to overwrite
        $this->logger->notice(' ... '); //Line to overwrite

        $recordsStoredCount = 0;
        $recordsSkippedCount = 0;
        $rowCount = 0;
        $foundValue = false;


        //Initialize solani groups
        foreach ($dutchBreedValueTypes as $ordinal => $dutchBreedValueType) {
            $this->solani[$dutchBreedValueType] = [];
        }

        $onlyImportValidatedAnimalIds = $this->onlyImportValidatedAnimalIds();

        foreach ($ssv as $row) {

            $rowCount++;
            $animalId = $row[$firstColumnIndex];

            if ($onlyImportValidatedAnimalIds && !key_exists($animalId, $this->validatedAnimalIds)) {
                $recordsSkippedCount++;
                continue;
            }

            foreach ($dutchBreedValueTypes as $ordinal => $dutchBreedValueType) {
                //0-indexed solani column n starts at 0-indexed $ssv row[n+3] / column 4 in the file
                $key = $ordinal+self::ZERO_INDEXED_SOLANI_COLUMN+$firstColumnIndex;
                $value = $row[$key];
                if($value !== null && $value !== '') {
                    //NOTE! Zero and negative Solani values are valid!
                    $this->solani[$dutchBreedValueType][$animalId] = floatval($value);
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

            if($rowCount%self::PRINT_BATCH_SIZE === 0) {
                $this->overwriteNotice('Solani records stored|skipped: '.$recordsStoredCount.'|'.$recordsSkippedCount .'   row: '.$rowCount.'|'.$totalRowCount);
            }
        }
        if(self::TEST_COLUMN_ALIGNMENT) { dump($this->solani); }

        $this->logger->notice('Solani records stored|skipped: '.$recordsStoredCount.'|'.$recordsSkippedCount);
    }


    private function onlyImportValidatedAnimalIds() {
        return $this->currentBreedType == MixBlupAnalysis::TAIL_LENGTH;
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

        $totalRowCount = count($ssv);
        if($totalRowCount === 0) {
            $this->logger->notice($filename . ' is empty');
            return;
        }

        $dutchBreedValueTypes = $filename === Filename::RELANI_INDIRECT ? MixBlupParseInstruction::getIndirect($this->currentBreedType) :
            MixBlupParseInstruction::get($this->currentBreedType, false);
        if(count($dutchBreedValueTypes) === 0) {
            $this->logger->error($this->currentBreedType . ' extracted currentBreedType is not a valid MixBlupAnalysis type');
            return;
        }

        $this->logger->notice('Parsing '.$filename.' file for '.$this->currentBreedType. ' ... ');
        $this->logger->notice(' ... '); //Line to overwrite
        $this->logger->notice(' ... '); //Line to overwrite

        $recordsStoredCount = 0;
        $recordsSkippedCount = 0;
        $rowCount = 0;
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

        //Initialize relani groups
        foreach ($dutchBreedValueTypes as $ordinal => $dutchBreedValueType) {
            switch ($filename)
            {
                case Filename::RELANI_DIRECT:
                    $this->relaniDirect[$dutchBreedValueType] = [];
                    break;

                case Filename::RELANI_INDIRECT:
                    $this->relaniIndirect[$dutchBreedValueType] = [];
                    break;

                default:
                    $this->relani[$dutchBreedValueType] = [];
                    break;
            }
        }

        $onlyImportValidatedAnimalIds = $this->onlyImportValidatedAnimalIds();

        foreach ($ssv as $row) {

            $rowCount++;
            $animalId = $row[$firstColumnIndex];

            if ($onlyImportValidatedAnimalIds && !key_exists($animalId, $this->validatedAnimalIds)) {
                $recordsSkippedCount++;
                continue;
            }

            foreach ($dutchBreedValueTypes as $ordinal => $dutchBreedValueType) {

                //0-indexed relani column n starts at 0-indexed $ssv row[n+3] / column 4 in the file
                $key = $ordinal+self::ZERO_INDEXED_RELANI_COLUMN+$firstColumnIndex;
                $value = $row[$key];
                if($value !== null && $value !== '') {
                    $floatValue = floatval($value);

                    if(!NumberUtil::isFloatZero($floatValue, MixBlupSetting::FLOAT_ACCURACY)) {
                        switch ($filename)
                        {
                            case Filename::RELANI_DIRECT:
                                $this->relaniDirect[$dutchBreedValueType][$animalId] = $floatValue;
                                break;

                            case Filename::RELANI_INDIRECT:
                                $this->relaniIndirect[$dutchBreedValueType][$animalId] = $floatValue;
                                break;

                            default:
                                $this->relani[$dutchBreedValueType][$animalId] = $floatValue;
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
            if($rowCount%self::PRINT_BATCH_SIZE === 0) {
                $this->overwriteNotice($filename . ' records stored|skipped: '.$recordsStoredCount.'|'.$recordsSkippedCount . '   row: '.$rowCount.'|'.$totalRowCount);
            }
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
        if(key_exists($dutchBreedValueType, $this->currentBreedValueExistsByAnimalIdForGenerationDate)) {
            if($this->currentBreedValueExistsByAnimalIdForGenerationDate[$dutchBreedValueType]) {
                return key_exists($animalId, $this->currentBreedValueExistsByAnimalIdForGenerationDate[$dutchBreedValueType]);
            }
        }
        return false;
    }


    private function processBreedValues()
    {
        $this->processBreedValuesBase(Filename::RELANI);
    }


    private function processDirectBreedValues()
    {
        $this->logger->notice('Processing DIRECT breedValues ...');
        $this->processBreedValuesBase(Filename::RELANI_DIRECT);
    }


    private function processIndirectBreedValues()
    {
        $this->logger->notice('Processing INDIRECT breedValues ...');
        $this->processBreedValuesBase(Filename::RELANI_INDIRECT, true);
    }


    private function processBreedValuesBase($relaniType, $hasIndirectSuffix = false)
    {
        $this->sqlBatchSets = [];
        $this->persistenceSetErrorsCount = 0;
        $this->totalSavedCount = 0;
        $this->toSaveBatchCount = 0;
        $this->valueAlreadyExistsCount = 0;
        $this->nullAccuracyCount = 0;
        $this->processScanCount = 0;

        DoctrineUtil::updateTableSequence($this->conn, [BreedValue::getTableName()]);

        switch ($relaniType) {

            case Filename::RELANI_DIRECT:

                foreach ($this->relaniDirect as $dutchBreedValueTypeKeyInSolaniArray => $relaniValues) {
                    $this->processScanCount++;
                    $dutchBreedValueTypeForDatabase = $dutchBreedValueTypeKeyInSolaniArray;

                    $this->logger->notice('Processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues ...');
                    $this->logger->notice(' ... '); //Line to overwrite
                    $this->logger->notice(' ... '); //Line to overwrite

                    $breedValueTypeId = $this->breedValueTypeIdsByDutchDescription[$dutchBreedValueTypeForDatabase];

                    $this->printBreedValueProcessMessage($dutchBreedValueTypeKeyInSolaniArray);
                    //NOTE! Null Relani values are already filtered out. So the Relani array will be smaller than the Solani array!
                    foreach ($relaniValues as $animalId => $relani) {
                        $this->processBreedValue($dutchBreedValueTypeKeyInSolaniArray, $dutchBreedValueTypeForDatabase, $animalId, $relani, $breedValueTypeId);
                    }

                    if(count($this->sqlBatchSets) > 0) { $this->persistBreedValueBySql(); }
                    $this->logger->notice('Finished processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues.');
                }

                break;

            case Filename::RELANI_INDIRECT:

                foreach ($this->relaniIndirect as $dutchBreedValueTypeKeyInSolaniArray => $relaniValues) {
                    $this->processScanCount++;
                    $dutchBreedValueTypeForDatabase = $dutchBreedValueTypeKeyInSolaniArray;
                    if($hasIndirectSuffix) {
                        $dutchBreedValueTypeForDatabase = $this->removeIndirectSuffix($dutchBreedValueTypeKeyInSolaniArray);
                        if($dutchBreedValueTypeForDatabase === BreedValueTypeConstant::BIRTH_PROGRESS) {
                            //The indirect birthProgress value for IDM is actually the birthDeliveryProgress value
                            $dutchBreedValueTypeForDatabase = BreedValueTypeConstant::BIRTH_DELIVERY_PROGRESS;
                        }
                    }

                    $this->logger->notice('Processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues ...');
                    $this->logger->notice(' ... '); //Line to overwrite
                    $this->logger->notice(' ... '); //Line to overwrite

                    $breedValueTypeId = $this->breedValueTypeIdsByDutchDescription[$dutchBreedValueTypeForDatabase];

                    $this->printBreedValueProcessMessage($dutchBreedValueTypeKeyInSolaniArray);
                    //NOTE! Null Relani values are already filtered out. So the Relani array will be smaller than the Solani array!
                    foreach ($relaniValues as $animalId => $relani) {
                        $this->processBreedValue($dutchBreedValueTypeKeyInSolaniArray, $dutchBreedValueTypeForDatabase, $animalId, $relani, $breedValueTypeId);
                    }

                    if(count($this->sqlBatchSets) > 0) { $this->persistBreedValueBySql(); }
                    $this->logger->notice('Finished processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues.');
                }

                break;

            default:

                foreach ($this->relani as $dutchBreedValueTypeKeyInSolaniArray => $relaniValues) {
                    $this->processScanCount++;
                    $dutchBreedValueTypeForDatabase = $dutchBreedValueTypeKeyInSolaniArray;

                    $this->logger->notice('Processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues ...');
                    $this->logger->notice(' ... '); //Line to overwrite
                    $this->logger->notice(' ... '); //Line to overwrite

                    $breedValueTypeId = $this->breedValueTypeIdsByDutchDescription[$dutchBreedValueTypeForDatabase];

                    $this->printBreedValueProcessMessage($dutchBreedValueTypeKeyInSolaniArray);
                    //NOTE! Null Relani values are already filtered out. So the Relani array will be smaller than the Solani array!
                    foreach ($relaniValues as $animalId => $relani) {
                        $this->processBreedValue($dutchBreedValueTypeKeyInSolaniArray, $dutchBreedValueTypeForDatabase, $animalId, $relani, $breedValueTypeId);
                    }

                    if(count($this->sqlBatchSets) > 0) { $this->persistBreedValueBySql(); }
                    $this->logger->notice('Finished processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues.');
                }

                break;
        }


        $this->logger->notice('Finished processing breedvalues set!');
    }


    private function processBreedValue($dutchBreedValueTypeKeyInSolaniArray, $dutchBreedValueTypeForDatabase, $animalId, $relani, $breedValueTypeId)
    {
        if(!key_exists($animalId, $this->animalIdsInDatabase)) {
            $this->missingAnimalIds[$animalId] = $animalId;
            return;
        }

        if($this->breedValueAlreadyExists($dutchBreedValueTypeForDatabase, $animalId)) {
            $this->valueAlreadyExistsCount++;
            if($this->processScanCount%self::PRINT_BATCH_SIZE === 0) {
                $this->printBreedValueProcessMessage($dutchBreedValueTypeKeyInSolaniArray);
            }
            return;
        }

        //Note! a 0.000 value in the relani file refers to a null/missing value.
        //Do not persist these in the database.
        if(NumberUtil::isFloatZero($relani, MixBlupSetting::FLOAT_ACCURACY)) {
            if($this->processScanCount%self::PRINT_BATCH_SIZE === 0) {
                $this->printBreedValueProcessMessage($dutchBreedValueTypeKeyInSolaniArray);
            }
            $this->nullAccuracyCount++;
            return;
        }

        $solani = null;
        if(key_exists($dutchBreedValueTypeKeyInSolaniArray, $this->solani)) {
            if($this->solani[$dutchBreedValueTypeKeyInSolaniArray]) {
                if(key_exists($animalId, $this->solani[$dutchBreedValueTypeKeyInSolaniArray]))
                    $solani = $this->solani[$dutchBreedValueTypeKeyInSolaniArray][$animalId];

                $breedValueInsertString = $this->writeBreedValueInsertValuesString($breedValueTypeId, $animalId, $solani, $relani);

                $this->sqlBatchSets[$animalId] = $breedValueInsertString;
                $this->totalSavedCount++;
                $this->toSaveBatchCount++;

                $this->addToCurrentBreedValueExistsByAnimalIdForGenerationDate($dutchBreedValueTypeForDatabase, $animalId);

                if($this->totalSavedCount%self::BATCH_SIZE === 0) {
                    $this->persistBreedValueBySql();
                    $this->sqlBatchSets = [];
                    $this->toSaveBatchCount = 0;

                    $this->printBreedValueProcessMessage($dutchBreedValueTypeKeyInSolaniArray);
                }
            }
        }

        if($this->processScanCount%self::PRINT_BATCH_SIZE === 0) {
            $this->printBreedValueProcessMessage($dutchBreedValueTypeKeyInSolaniArray);
        }
    }


    /**
     * @return null|string
     */
    private function getGenerationDateStringFromKey()
    {
        $generationDateString = strtr($this->key, ['_' => ' ']);
        if (TimeUtil::isValidDateTime($generationDateString, SqlUtil::DATE_TIME_FORMAT)) {
            return $generationDateString;
        }
        return null;
    }


    /**
     * @return bool
     */
    private function hasLambMeatOutputFiles()
    {
        return $this->hasOutputFilesByFilenamePart(
            [
                MixBlupAnalysis::LAMB_MEAT,
                MixBlupAnalysis::TAIL_LENGTH,
            ]
        );
    }


    /**
     * @return bool
     */
    private function hasWormResistanceOutputFiles()
    {
        return $this->hasOutputFilesByFilenamePart(MixBlupAnalysis::WORM_RESISTANCE);
    }


    /**
     * @return bool
     */
    private function hasFertilityOutputFiles()
    {
        return $this->hasOutputFilesByFilenamePart(
            [
                MixBlupAnalysis::BIRTH_PROGRESS,
                MixBlupAnalysis::FERTILITY,
                MixBlupAnalysis::FERTILITY_1,
                MixBlupAnalysis::FERTILITY_2,
                MixBlupAnalysis::FERTILITY_3,
                MixBlupAnalysis::FERTILITY_4,
            ]
        );
    }


    /**
     * @return bool
     */
    private function hasExteriorOutputFiles()
    {
        return $this->hasOutputFilesByFilenamePart(
            [
                MixBlupAnalysis::EXTERIOR_LEG_WORK,
                MixBlupAnalysis::EXTERIOR_MUSCULARITY,
                MixBlupAnalysis::EXTERIOR_PROGRESS,
                MixBlupAnalysis::EXTERIOR_PROPORTION,
                MixBlupAnalysis::EXTERIOR_SKULL,
                MixBlupAnalysis::EXTERIOR_TYPE,
            ]
        );
    }


    /**
     * @param string[]|string $filenameParts
     * @return bool
     */
    private function hasOutputFilesByFilenamePart($filenameParts)
    {
        if (is_string($filenameParts)) {
            $filenameParts = [$filenameParts];
        }

        $searchKey = implode(',', $filenameParts);
        if (key_exists($searchKey, $this->hasFilenameArray)) {
            return $this->hasFilenameArray[$searchKey];
        }

        foreach ([$this->bulkFiles, $this->relsol] as $set) {
            foreach ($set as $filenameWithExtension) {
                foreach ($filenameParts as $filenamePart) {
                    if (strpos($filenameWithExtension, $filenamePart) !== false) {

                        $this->hasFilenameArray[$searchKey] = true;
                        return $this->hasFilenameArray[$searchKey];
                    }
                }
            }
        }

        $this->hasFilenameArray[$searchKey] = false;
        return $this->hasFilenameArray[$searchKey];
    }


    private function updateResultTableBreedValuesAndTheirPrerequisites()
    {
        $detectedAnalysisTypes = [];
        if ($this->hasLambMeatOutputFiles()) {
            $detectedAnalysisTypes[MixBlupType::LAMB_MEAT_INDEX] = MixBlupType::LAMB_MEAT_INDEX;
            $this->logger->notice('LambMeatOutputFilename found in message...');
        }
        if ($this->hasExteriorOutputFiles()) {
            $detectedAnalysisTypes[MixBlupType::EXTERIOR] = MixBlupType::EXTERIOR;
            $this->logger->notice('ExteriorOutputFilename found in message...');
        }
        if ($this->hasFertilityOutputFiles()) {
            $detectedAnalysisTypes[MixBlupType::FERTILITY] = MixBlupType::FERTILITY;
            $this->logger->notice('FertilityOutputFilename found in message...');
        }
        if ($this->hasWormResistanceOutputFiles()) {
            $detectedAnalysisTypes[MixBlupType::WORM] = MixBlupType::WORM;
            $this->logger->notice('WormOutputFilename found in message...');
        }

        $this->breedValuesResultTableUpdater->update($detectedAnalysisTypes,
            true,false,
            true, true, $this->getGenerationDateStringFromKey());
    }


    /**
     * @param $dutchBreedValueTypeKeyInSolaniArray
     * @return string
     */
    private function printBreedValueProcessMessage($dutchBreedValueTypeKeyInSolaniArray)
    {
        $this->overwriteNotice('Processing '.$dutchBreedValueTypeKeyInSolaniArray.' breedValues count saved|toSave|alreadyExists|null: '.$this->totalSavedCount.'|'.$this->toSaveBatchCount.'|'.$this->valueAlreadyExistsCount.'|'.$this->nullAccuracyCount);
    }


    /**
     * @param int $breedValueTypeId
     * @param int $animalId
     * @param float $solani
     * @param float $relani
     * @return string
     */
    private function writeBreedValueInsertValuesString($breedValueTypeId, $animalId, $solani, $relani)
    {
        return "(nextval('breed_value_id_seq'),".$animalId.",".$breedValueTypeId.",NOW(),'".$this->key."','". $solani."','".$relani."')";
    }


    /**
     * @return integer
     */
    private function persistBreedValueBySql()
    {
        if(count($this->sqlBatchSets) === 0) { return 0; }

        $updateCount = 0;

        try {

            $sql = "INSERT INTO breed_value (id, animal_id, type_id, log_date, generation_date, value, reliability) VALUES ".implode(',', $this->sqlBatchSets);
            $updateCount = SqlUtil::updateWithCount($this->conn, $sql);


        } catch (\Exception $exception) {

            if (Validator::isMissingAnimalIdForeignKeyException($exception)) {
                $removedAnyMissingAnimals = $this->removeMissingAnimalsFromSqlBatchSets();
                if ($removedAnyMissingAnimals) {
                    return $this->persistBreedValueBySql();
                }
            }
            $this->logger->error($exception->getTraceAsString());
            $this->logger->error($exception->getMessage());
            $this->persistenceSetErrorsCount += count($this->sqlBatchSets);
        }

        $this->sqlBatchSets = [];
        $this->toSaveBatchCount = 0;
        $this->totalSavedCount += $updateCount;

        return $updateCount;
    }


    /**
     * @return bool is true if any missing animals were removed
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeMissingAnimalsFromSqlBatchSets()
    {
        $missingAnimalIds = SqlUtil::getMissingAnimalIds($this->conn, array_keys($this->sqlBatchSets));
        if (count($missingAnimalIds) === 0) {
            return false;
        }
        $this->sqlBatchSets = ArrayUtil::removeKeys($this->sqlBatchSets, $missingAnimalIds);
        return true;
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


    /**
     * @return int
     */
    public function removeDuplicateBreedValues()
    {
        $this->logger->notice('Deleting duplicate breedValues ...');
        $deleteCount = self::deleteDuplicateBreedValues($this->conn, $this->key);
        $message = $deleteCount > 0 ? $deleteCount . ' duplicate breedValues deleted' : 'No duplicate breedValues were found';
        $this->logger->notice($message);
        return $deleteCount;
    }


    /**
     * @param Connection $conn
     * @param string $dateString
     * @return int
     */
    public static function deleteDuplicateBreedValues(Connection $conn, $dateString = null)
    {
        $dateFilter = is_string($dateString) ? "a.generation_date = '".$dateString."' AND " : '';

        $sql = "DELETE FROM breed_value a USING (
                    SELECT MIN(id) as id, animal_id, type_id, generation_date, value as value, reliability
                    FROM breed_value
                    GROUP BY animal_id, type_id, generation_date, value, reliability HAVING COUNT(*) > 1
                ) b
                WHERE
                  a.animal_id = b.animal_id AND
                  a.type_id = b.type_id AND
                  a.generation_date = b.generation_date AND
                  $dateFilter
                  a.value = b.value AND
                  a.reliability = b.reliability AND
                  a.id <> b.id";
        return SqlUtil::updateWithCount($conn, $sql);
    }
}
