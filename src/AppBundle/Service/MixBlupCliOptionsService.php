<?php


namespace AppBundle\Service;


use AppBundle\Cache\BreedValuesResultTableUpdater;
use AppBundle\Component\MixBlup\MixBlupInputFileValidator;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Enumerator\PedigreeAbbreviation;
use AppBundle\Service\Migration\LambMeatIndexMigrator;
use AppBundle\Service\Migration\MixBlupAnalysisTypeMigrator;
use AppBundle\Service\Migration\WormResistanceIndexMigrator;
use AppBundle\Service\Report\BreedValuesOverviewReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class MixBlupCliOptionsService
 */
class MixBlupCliOptionsService
{
    const DEFAULT_OPTION = 0;
    const DEFAULT_UBN = 1674459;
    const DEFAULT_MIN_UBN = 0;
    const DEFAULT_GENERATION_DATE_STRING = "2017-01-01 00:00:00";

    /** @var ObjectManager|EntityManagerInterface */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var Logger */
    private $logger;

    /** @var BreedValuesOverviewReportService */
    private $breedValuesOverviewReportService;
    /** @var BreedValuePrinter */
    private $breedValuePrinter;
    /** @var BreedValueService */
    private $breedValueService;
    /** @var BreedIndexService */
    private $breedIndexService;
    /** @var ExcelService */
    private $excelService;
    /** @var LambMeatIndexMigrator */
    private $lambMeatIndexMigrator;
    /** @var WormResistanceIndexMigrator */
    private $wormResistanceIndexMigrator;
    /** @var MixBlupInputFilesService */
    private $mixBlupInputFilesService;
    /** @var MixBlupInputFileValidator */
    private $mixBlupInputFileValidator;
    /** @var MixBlupOutputFilesService */
    private $mixBlupOutputFilesService;
    /** @var PedigreeRegisterOverviewReportService */
    private $pedigreeRegisterOverviewReportService;
    /** @var MixBlupAnalysisTypeMigrator */
    private $mixBlupAnalysisTypeMigrator;
    /** @var BreedValuesResultTableUpdater */
    private $breedValuesResultTableUpdater;

    public function __construct(EntityManagerInterface $em, Logger $logger,
                                BreedValuesOverviewReportService $breedValuesOverviewReportService,
                                BreedValuePrinter $breedValuePrinter,
                                BreedValueService $breedValueService,
                                BreedIndexService $breedIndexService,
                                ExcelService $excelService,
                                LambMeatIndexMigrator $lambMeatIndexMigrator,
                                WormResistanceIndexMigrator $wormResistanceIndexMigrator,
                                MixBlupInputFilesService $mixBlupInputFilesService,
                                MixBlupInputFileValidator $mixBlupInputFileValidator,
                                MixBlupOutputFilesService $mixBlupOutputFilesService,
                                MixBlupAnalysisTypeMigrator $mixBlupAnalysisTypeMigrator,
                                PedigreeRegisterOverviewReportService $pedigreeRegisterOverviewReportService,
                                BreedValuesResultTableUpdater $breedValuesResultTableUpdater
    )
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;

        $this->breedValuesOverviewReportService = $breedValuesOverviewReportService;
        $this->breedValuePrinter = $breedValuePrinter;
        $this->breedValueService = $breedValueService;
        $this->breedIndexService = $breedIndexService;
        $this->excelService = $excelService;
        $this->lambMeatIndexMigrator = $lambMeatIndexMigrator;
        $this->wormResistanceIndexMigrator = $wormResistanceIndexMigrator;
        $this->mixBlupInputFilesService = $mixBlupInputFilesService;
        $this->mixBlupInputFileValidator = $mixBlupInputFileValidator;
        $this->mixBlupOutputFilesService = $mixBlupOutputFilesService;
        $this->mixBlupAnalysisTypeMigrator = $mixBlupAnalysisTypeMigrator;
        $this->pedigreeRegisterOverviewReportService = $pedigreeRegisterOverviewReportService;
        $this->breedValuesResultTableUpdater = $breedValuesResultTableUpdater;
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function run(CommandUtil $cmdUtil)
    {
        if ($this->cmdUtil === null) { $this->cmdUtil = $cmdUtil; }

        //Print intro
        $cmdUtil->writelnClean(CommandUtil::generateTitle(CommandTitle::MIXBLUP));
        $cmdUtil->writelnClean([DoctrineUtil::getDatabaseHostAndNameString($this->em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate & Upload MixBlupInputFiles and send message to MixBlup queue', "\n",
            '2: Download and process MixBlup output files (relani & solani)', "\n",
            '3: Generate MixBlup instruction files only', "\n",
            '4: Initialize blank genetic bases', "\n",
            '5: Set minimum reliability for all breedValueTypes by accuracy option', "\n",
            '6: Update/Insert LambMeatIndex values by generationDate (excl. resultTable update)', "\n",
            '7: Update breedIndex & breedValue normal distribution values', "\n",
            '========================================================================', "\n",
            '10: Initialize BreedIndexType and BreedValueType', "\n",
            '11: Initialize MixBlupAnalysisTypes', "\n",
            '12: Delete all duplicate breedValues', "\n",
            '========================================================================', "\n",
            '13: Update result_table_breed_grades values and accuracies for all breedValue and breedIndex types (including prerequisite options)', "\n",
            '14: Update result_table_breed_grades values and accuracies for '.MixBlupType::LAMB_MEAT_INDEX.' types (excluding prerequisites)', "\n",
            '15: Update result_table_breed_grades values and accuracies for '.MixBlupType::FERTILITY.' types (excluding prerequisites)', "\n",
            '16: Update result_table_breed_grades values and accuracies for '.MixBlupType::WORM.' types (excluding prerequisites)', "\n",
            '17: Update result_table_breed_grades values and accuracies for '.MixBlupType::EXTERIOR.' types (excluding prerequisites)', "\n",
            '========================================================================', "\n",
            '18: Initialize lambMeatIndexCoefficients', "\n",
            '19: Initialize wormResistanceIndexCoefficients', "\n",
            '========================================================================', "\n",
            '20: Validate ubnOfBirth format as !BLOCK in DataVruchtb.txt in mixblup cache folder', "\n",
            '21: Validate ubnOfBirth format as !BLOCK in PedVruchtb.txt in mixblup cache folder', "\n",
            '========================================================================', "\n",
            '30: Print separate csv files of latest breedValues for all ubns', "\n",
            '31: Print separate csv files of latest breedValues for chosen ubn', "\n",
            '========================================================================', "\n",
            '40: Clear excel cache folder', "\n",
            '41: Print CSV file for CF pedigree register', "\n",
            '42: Print CSV file for NTS, TSNH, LAX pedigree registers', "\n",
            '43: Print CSV file Breedvalues overview all animals on a ubn, with atleast one breedValue', "\n",
            '44: Print CSV file Breedvalues overview all animals on a ubn, even those without a breedValue', "\n",
            '========================================================================', "\n",
            '50: Generate & Upload EXTERIOR MixBlupInputFiles and send message to MixBlup queue', "\n",
            '51: Generate & Upload LAMB MEAT INDEX MixBlupInputFiles and send message to MixBlup queue', "\n",
            '52: Generate & Upload FERTILITY MixBlupInputFiles and send message to MixBlup queue', "\n",
            '53: Generate & Upload WORM MixBlupInputFiles and send message to MixBlup queue', "\n",
            'other: EXIT ', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1: $this->mixBlupInputFilesService->run(); break;
            case 2: $this->mixBlupOutputFilesService->run(); break;
            case 3: $this->mixBlupInputFilesService->writeInstructionFiles(); break;
            case 4: $this->breedValueService->initializeBlankGeneticBases(); break;
            case 5: $this->breedValueService->setMinReliabilityForAllBreedValueTypesByAccuracyOption($this->cmdUtil); break;
            case 6: $this->updateLambMeatIndexesByGenerationDate(); break;
            case 7: $this->updateBreedIndexAndBreedValueNormalDistributions(); break;


            case 10:
                $this->breedIndexService->initializeBreedIndexType();
                $this->breedValueService->initializeBreedValueType();
                $this->breedValueService->initializeCustomBreedValueTypeSettings();
                $this->breedValueService->initializeGraphOrdinalData();
                $this->breedValueService->linkBreedValueGraphGroups();
                break;
            case 11:
                $this->mixBlupAnalysisTypeMigrator->run($this->cmdUtil);
                break;
            case 12:
                $deleteCount = MixBlupOutputFilesService::deleteDuplicateBreedValues($this->conn);
                $message = $deleteCount > 0 ? $deleteCount . ' duplicate breedValues were deleted' : 'No duplicate breedValues found';
                $this->cmdUtil->writeln($message);
                break;

            case 13: $this->updateAllResultTableValuesAndPrerequisites(); break;
            case 14: $this->breedValuesResultTableUpdater->update([MixBlupType::LAMB_MEAT_INDEX]); break;
            case 15: $this->breedValuesResultTableUpdater->update([MixBlupType::FERTILITY]); break;
            case 16: $this->breedValuesResultTableUpdater->update([MixBlupType::WORM]); break;
            case 17: $this->breedValuesResultTableUpdater->update([MixBlupType::EXTERIOR]); break;

            case 18: $this->lambMeatIndexMigrator->migrate(); break;
            case 19: $this->wormResistanceIndexMigrator->migrate(); break;

            case 20: $this->mixBlupInputFileValidator->validateUbnOfBirthInDataFile($this->cmdUtil); break;
            case 21: $this->mixBlupInputFileValidator->validateUbnOfBirthInPedigreeFile($this->cmdUtil); break;

            case 30: $this->printBreedValuesAllUbns(); break;
            case 31: $this->printBreedValuesByUbn(); break;

            case 40: $this->excelService->clearCacheFolder(); break;
            case 41:
                $filepath = $this->pedigreeRegisterOverviewReportService->generateFileByType(PedigreeAbbreviation::CF, false, FileType::CSV);
                $this->logger->notice($filepath);
                break;
            case 42:
                $filepath = $this->pedigreeRegisterOverviewReportService->generateFileByType(PedigreeAbbreviation::NTS,false, FileType::CSV);
                $this->logger->notice($filepath);
                break;
            case 43: $filepath = $this->breedValuesOverviewReportService->generate(FileType::CSV, false, false, false);
                $this->logger->notice($filepath);
                break;
            case 44: $filepath = $this->breedValuesOverviewReportService->generate(FileType::CSV, false, true, false);
                $this->logger->notice($filepath);
                break;

            case 50: $this->mixBlupInputFilesService->runExterior(); break;
            case 51: $this->mixBlupInputFilesService->runLambMeatIndex(); break;
            case 52: $this->mixBlupInputFilesService->runFertility(); break;
            case 53: $this->mixBlupInputFilesService->runWorm(); break;

            default: return;
        }
        $this->run($this->cmdUtil);
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    private function updateAllResultTableValuesAndPrerequisites()
    {
        /*
         * Options
         */
        $updateBreedIndexes = $this->cmdUtil->generateConfirmationQuestion('Update BreedIndexes? (y/n, default is false)');
        $this->logger->notice('Update BreedIndexes: '. StringUtil::getBooleanAsString($updateBreedIndexes));

        $updateNormalDistributions = $this->cmdUtil->generateConfirmationQuestion('Update NormalDistributions? (y/n, default is false)');
        $this->logger->notice('Update NormalDistributions: '. StringUtil::getBooleanAsString($updateNormalDistributions));

        $generationDateString = $this->cmdUtil->generateQuestion('Insert custom GenerationDateString (default: The generationDateString of the last inserted breedValue will be used)', null);
        $this->logger->notice('GenerationDateString to be used: '.$this->breedValuesResultTableUpdater->getGenerationDateString($generationDateString));
        // End of options

        $this->breedValuesResultTableUpdater->update(
            [],
            $updateBreedIndexes,
            $updateNormalDistributions,
            $generationDateString
        );
    }


    private function printBreedValuesAllUbns()
    {
        do {
            $ubn = $this->cmdUtil->generateQuestion('insert minimum ubn (default: '.self::DEFAULT_MIN_UBN.')', self::DEFAULT_MIN_UBN);
        } while(!ctype_digit($ubn) && !is_int($ubn));
        $this->cmdUtil->writeln('Generating breedValues csv file with minimum UBN of: '.$ubn.' ...');
        $this->breedValuePrinter->printBreedValuesAllUbns($ubn);
        $this->cmdUtil->writeln('Generated breedValues csv file with minimum UBN of: '.$ubn.' ...');
    }


    private function printBreedValuesByUbn()
    {
        do {
            $ubn = $this->cmdUtil->generateQuestion('insert ubn (default: '.self::DEFAULT_UBN.')', self::DEFAULT_UBN);
        } while(!ctype_digit($ubn) && !is_int($ubn));
        $this->cmdUtil->writeln('Generating breedValues csv file for UBN: '.$ubn.' ...');
        $this->breedValuePrinter->printBreedValuesByUbn($ubn);
        $this->cmdUtil->writeln('BreedValues csv file generated for UBN: '.$ubn);
    }


    private function updateLambMeatIndexesByGenerationDate()
    {
        do {
            $generationDateString = $this->cmdUtil->generateQuestion('insert generationDate string in following format: 2017-01-01 00:00:00 (default: '.self::DEFAULT_GENERATION_DATE_STRING.')', self::DEFAULT_GENERATION_DATE_STRING, false);
        } while(!TimeUtil::isValidDateTime($generationDateString));

        $this->breedIndexService->updateLambMeatIndexes($generationDateString);
    }


    private function updateBreedIndexAndBreedValueNormalDistributions()
    {
        /*
         * Options
         */
        $updateBreedIndexNormalDistributions = $this->cmdUtil->generateConfirmationQuestion('Update BreedIndex normal distributions?', true, true);

        $updateBreedValueNormalDistributions = $this->cmdUtil->generateConfirmationQuestion('Update BreedValue normal distributions?', true, true);

        $overwriteExistingValues = $this->cmdUtil->generateConfirmationQuestion('OVERWRITE EXISTING VALUES?', true, true);

        // End of options

        if ($updateBreedIndexNormalDistributions) {
            $this->breedValuesResultTableUpdater->updateAllBreedIndexNormalDistributions($overwriteExistingValues);
        }

        if ($updateBreedValueNormalDistributions) {
            $this->breedValuesResultTableUpdater->updateAllBreedValueNormalDistributions($overwriteExistingValues);
        }

    }
}