<?php


namespace AppBundle\Service;


use AppBundle\Cache\BreedValuesResultTableUpdater;
use AppBundle\Component\MixBlup\MixBlupInputFileValidator;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\PedigreeAbbreviation;
use AppBundle\Service\Migration\LambMeatIndexMigrator;
use AppBundle\Service\Report\BreedValuesOverviewReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
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
    /** @var MixBlupInputFilesService */
    private $mixBlupInputFilesService;
    /** @var MixBlupInputFileValidator */
    private $mixBlupInputFileValidator;
    /** @var MixBlupOutputFilesService */
    private $mixBlupOutputFilesService;
    /** @var PedigreeRegisterOverviewReportService */
    private $pedigreeRegisterOverviewReportService;


    public function __construct(EntityManagerInterface $em, Logger $logger,
                                BreedValuesOverviewReportService $breedValuesOverviewReportService,
                                BreedValuePrinter $breedValuePrinter,
                                BreedValueService $breedValueService,
                                BreedIndexService $breedIndexService,
                                ExcelService $excelService,
                                LambMeatIndexMigrator $lambMeatIndexMigrator,
                                MixBlupInputFilesService $mixBlupInputFilesService,
                                MixBlupInputFileValidator $mixBlupInputFileValidator,
                                MixBlupOutputFilesService $mixBlupOutputFilesService,
                                PedigreeRegisterOverviewReportService $pedigreeRegisterOverviewReportService)
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
        $this->mixBlupInputFilesService = $mixBlupInputFilesService;
        $this->mixBlupInputFileValidator = $mixBlupInputFileValidator;
        $this->mixBlupOutputFilesService = $mixBlupOutputFilesService;
        $this->pedigreeRegisterOverviewReportService = $pedigreeRegisterOverviewReportService;
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
            '========================================================================', "\n",
            '10: Initialize BreedIndexType and BreedValueType', "\n",
            '11: Delete all duplicate breedValues', "\n",
            '12: Update result_table_breed_grades values and accuracies for all breedValue and breedIndex types', "\n",
            '13: Initialize lambMeatIndexCoefficients', "\n",
            '========================================================================', "\n",
            '20: Validate ubnOfBirth format as !BLOCK in DataVruchtb.txt in mixblup cache folder', "\n",
            '21: Validate ubnOfBirth format as !BLOCK in PedVruchtb.txt in mixblup cache folder', "\n",
            '========================================================================', "\n",
            '30: Print separate csv files of latest breedValues for all ubns', "\n",
            '31: Print separate csv files of latest breedValues for chosen ubn', "\n",
            '========================================================================', "\n",
            '40: Clear excel cache folder', "\n",
            '41: Print excel file for CF pedigree register', "\n",
            '42: Print excel file for NTS, TSNH, LAX pedigree registers', "\n",
            '43: Print excel file Breedvalues overview all animals on a ubn', "\n",
            'other: EXIT ', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1: $this->mixBlupInputFilesService->run(); break;
            case 2: $this->mixBlupOutputFilesService->run(); break;
            case 3: $this->mixBlupInputFilesService->writeInstructionFiles(); break;
            case 4: $this->breedValueService->initializeBlankGeneticBases(); break;
            case 5: $this->breedValueService->setMinReliabilityForAllBreedValueTypesByAccuracyOption($this->cmdUtil); break;


            case 10:
                $this->breedIndexService->initializeBreedIndexType();
                $this->breedValueService->initializeBreedValueType();
                break;
            case 11:
                $deleteCount = MixBlupOutputFilesService::deleteDuplicateBreedValues($this->conn);
                $message = $deleteCount > 0 ? $deleteCount . ' duplicate breedValues were deleted' : 'No duplicate breedValues found';
                $this->cmdUtil->writeln($message);
                break;

            case 12:
                $breedValuesResultTableUpdater = new BreedValuesResultTableUpdater($this->em, $this->logger);
                $breedValuesResultTableUpdater->update();
                break;

            case 13: $this->lambMeatIndexMigrator->migrate(); break;

            case 20: $this->mixBlupInputFileValidator->validateUbnOfBirthInDataFile($this->cmdUtil); break;
            case 21: $this->mixBlupInputFileValidator->validateUbnOfBirthInPedigreeFile($this->cmdUtil); break;

            case 30: $this->printBreedValuesAllUbns(); break;
            case 31: $this->printBreedValuesByUbn(); break;

            case 40: $this->excelService->clearCacheFolder(); break;
            case 41:
                $filepath = $this->pedigreeRegisterOverviewReportService->generateFileByType(PedigreeAbbreviation::CF, false, FileType::XLS);
                $this->logger->notice($filepath);
                break;
            case 42:
                $filepath = $this->pedigreeRegisterOverviewReportService->generateFileByType(PedigreeAbbreviation::NTS,false, FileType::XLS);
                $this->logger->notice($filepath);
                break;
            case 43: $filepath = $this->breedValuesOverviewReportService->generate(FileType::XLS, false);
                $this->logger->notice($filepath);
                break;

            default: return;
        }
        $this->run($this->cmdUtil);
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
}