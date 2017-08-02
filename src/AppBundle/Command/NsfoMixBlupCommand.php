<?php

namespace AppBundle\Command;

use AppBundle\Cache\BreedValuesResultTableUpdater;
use AppBundle\Entity\BreedValue;
use AppBundle\Enumerator\PedigreeAbbreviation;
use AppBundle\Enumerator\ServiceId;
use AppBundle\Migration\BreedValuesSetMigrator;
use AppBundle\Migration\LambMeatIndexMigrator;
use AppBundle\Service\BreedIndexService;
use AppBundle\Service\BreedValuePrinter;
use AppBundle\Service\BreedValueService;
use AppBundle\Service\MixBlupInputFilesService;
use AppBundle\Service\MixBlupOutputFilesService;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NsfoMixBlupCommand
 * @package AppBundle\Command
 */
class NsfoMixBlupCommand extends ContainerAwareCommand
{
    const TITLE = 'MixBlup';
    const DEFAULT_OPTION = 0;
    const DEFAULT_UBN = 1674459;
    const DEFAULT_MIN_UBN = 0;

    const CREATE_TEST_FOLDER_IF_NULL = true;

    /** @var ObjectManager $em */
    private $em;
    /** @var Connection $conn */
    private $conn;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var Logger */
    private $logger;

    /** @var MixBlupInputFilesService */
    private $mixBlupInputFilesService;
    /** @var MixBlupOutputFilesService */
    private $mixBlupOutputFilesService;
    /** @var BreedIndexService */
    private $breedIndexService;
    /** @var BreedValueService */
    private $breedValueService;
    /** @var BreedValuePrinter */
    private $breedValuePrinter;

    protected function configure()
    {
        $this
            ->setName('nsfo:mixblup')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager|EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $this->getContainer()->get('logger');
        $this->rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->mixBlupInputFilesService = $this->getContainer()->get('app.mixblup.input');
        $this->mixBlupOutputFilesService = $this->getContainer()->get('app.mixblup.output');
        $this->breedIndexService = $this->getContainer()->get('app.breed.index');
        $this->breedValueService = $this->getContainer()->get('app.breed.value');
        $this->breedValuePrinter = $this->getContainer()->get('app.breed.valueprinter');

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln([DoctrineUtil::getDatabaseHostAndNameString($em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate & Upload MixBlupInputFiles and send message to MixBlup queue', "\n",
            '2: Download and process MixBlup output files (relani & solani)', "\n",
            '3: Generate MixBlup instruction files only', "\n",
            '4: Initialize blank genetic bases', "\n",
            '========================================================================', "\n",
            '10: Initialize BreedIndexType and BreedValueType', "\n",
            '11: Delete all duplicate breedValues', "\n",
            '12: Update result_table_breed_grades values and accuracies for all breedValue and breedIndex types', "\n",
            '13: Initialize lambMeatIndexCoefficients', "\n",
            '========================================================================', "\n",
            '30: Print separate csv files of latest breedValues for all ubns', "\n",
            '31: Print separate csv files of latest breedValues for chosen ubn', "\n",
            '========================================================================', "\n",
            '40: Clear excel cache folder', "\n",
            '41: Print excel file for CF pedigree register', "\n",
            '42: Print excel file for NTS, TSNH, LAX pedigree registers', "\n",
            'DEFAULT: Abort', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1: $this->mixBlupInputFilesService->run(); break;
            case 2: $this->mixBlupOutputFilesService->run(); break;
            case 3: $this->mixBlupInputFilesService->writeInstructionFiles(); break;
            case 4: $this->breedValueService->initializeBlankGeneticBases(); break;


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

            case 13: $this->getContainer()->get('app.migrator.lamb_meat_index')->migrate(); break;


            case 30: $this->printBreedValuesAllUbns(); break;
            case 31: $this->printBreedValuesByUbn(); break;

            case 40: $this->getContainer()->get(ServiceId::EXCEL_SERVICE)->clearCacheFolder(); break;
            case 41:
                $filepath = $this->getContainer()->get(ServiceId::PEDIGREE_REGISTER_REPORT)->generate(PedigreeAbbreviation::CF);
                $this->logger->notice($filepath);
                break;
            case 42:
                $filepath = $this->getContainer()->get(ServiceId::PEDIGREE_REGISTER_REPORT)->generate(PedigreeAbbreviation::NTS);
                $this->logger->notice($filepath);
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
        $output->writeln('DONE');


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
