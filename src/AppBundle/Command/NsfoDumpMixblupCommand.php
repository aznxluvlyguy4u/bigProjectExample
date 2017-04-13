<?php

namespace AppBundle\Command;

use AppBundle\Migration\MeasurementsFixer;
use AppBundle\MixBlup\Mixblup;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoDumpMixblupCommand extends ContainerAwareCommand
{
    const TITLE = 'Generate NSFO MiXBLUP files';
    const DEFAULT_OPTION = 0;

    /* To get all the measurements set both years to null */
    const START_YEAR_MEASUREMENT = null;
    const END_YEAR_MEASUREMENTS = null;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var ObjectManager */
    private $em;

    /** @var Connection */
    private $conn;

    /** @var string */
    private $outputFolder;

    /** @var string */
    private $mutationsFolder;
    
    /** @var string */
    private $rootDir;

    
    protected function configure()
    {
        $this
            ->setName('nsfo:dump:mixblup')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;
        $this->rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $this->conn = $this->em->getConnection();
        
        /* Setup folders */
        $this->outputFolder = $this->rootDir.MixBlupSetting::OUTPUT_FOLDER_PATH;
        NullChecker::createFolderPathIfNull($this->outputFolder);
        $this->mutationsFolder = $this->rootDir.MixBlupSetting::MUTATIONS_FOLDER_PATH;
        NullChecker::createFolderPathIfNull($this->mutationsFolder);


        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln([DoctrineUtil::getDatabaseHostAndNameString($this->em),'']);


        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate Mixblup Datafile', "\n",
            '   --------------------------------------------', "\n",
            '2: Generate Mixblup !BLOCKs', "\n",
            '3: Clear all Mixblup !BLOCK values', "\n",
            '4: Generate animalId-and-Date values for all measurements', "\n",
            '5: Delete duplicate measurements', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $this->generateMixblupFiles();
                break;

            case 2:
                $this->generateMixblupBlocks();
                break;

            case 3:
                $this->clearAllMixblupBlockValues();
                break;

            case 4:
                $this->generateAnimalIdAndDateStringsInAllMeasurements();
                break;

            case 5:
                $this->deleteDuplicateMeasurements();
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }


    private function generateMixblupBlocks()
    {
        $this->setMixblupBlockValuesForEwesAndNeutersWithoutMixblupBlocks();
        $this->setMixblupBlockValuesForRamsWithoutMixblupBlocks();
    }
    
    
    private function clearAllMixblupBlockValues()
    {
        if($this->cmdUtil->generateConfirmationQuestion('Are you sure you clear ALL MixblupBlock values? (y/n)')) {
            //Non-Export Ewes and Neuters with ubn of birth
            $sql = "UPDATE animal SET mixblup_block = NULL";
            $this->conn->exec($sql);
        }
    }
    
    
    private function setMixblupBlockValuesForAnimals($isRam = true) {

        if($isRam) {
            $typeSelection = "type = 'Ram'";
        } else {
            $typeSelection = "(type = 'Ewe' OR type = 'Neuter')";
        }

        /* Update these values in this exact order */

        //Export Animals
        $sql = "SELECT animal.id, country.id as country_id FROM animal INNER JOIN country ON uln_country_code = country.code WHERE mixblup_block IS NULL AND uln_country_code IS NOT NULL AND uln_country_code <> 'NL' AND ".$typeSelection;
        $results = $this->conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            $blockValue = $result['country_id']*10;
            $sql = "UPDATE animal SET mixblup_block = '".$blockValue."' WHERE id = ".$result['id'];
            $this->conn->exec($sql);
        }

        //Non-Export Animals with ubn of birth
        $sql = "UPDATE animal SET mixblup_block = CAST(ubn_of_birth AS INT) WHERE mixblup_block IS NULL AND animal.ubn_of_birth IS NOT NULL AND animal.ubn_of_birth <> '' AND ".$typeSelection;
        $this->conn->exec($sql);

        //If no ubn of birth is available, then take the ubn of the current location
        $sql = "SELECT a.id as id, l.ubn as ubn FROM animal a INNER JOIN location l ON (l.id = a.location_id) WHERE a.mixblup_block IS NULL AND a.location_id IS NOT NULL AND ".$typeSelection;
        $results = $this->conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            $blockValue = str_replace(' ','', $result['ubn']); //remove spaces
            $sql = "UPDATE animal SET mixblup_block = '".$blockValue."' WHERE id = ".$result['id'];
            $this->conn->exec($sql);
        }

        $sql = "UPDATE animal SET mixblup_block = '2' WHERE mixblup_block IS NULL AND ".$typeSelection;
        $this->conn->exec($sql);
    }


    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    private function setMixblupBlockValuesForEwesAndNeutersWithoutMixblupBlocks()
    {
        /* Update these values in this exact order */
        $this->setMixblupBlockValuesForAnimals(false);
    }


    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    private function setMixblupBlockValuesForRamsWithoutMixblupBlocks()
    {
        $studRamLabel = '1';
        $studRamUbnMinimum = 6;

        //First mark the (stud) rams with children on more than 5 ubns
        $sql = "SELECT parent_father_id as id, COUNT(DISTINCT(ubn_of_birth)) FROM animal WHERE parent_father_id IS NOT NULL GROUP BY parent_father_id";
        $results = $this->conn->query($sql)->fetchAll();
        // STUD RAMS
        foreach ($results as $result) {
            if($result['count'] >= $studRamUbnMinimum) {
                $sql = "UPDATE animal SET mixblup_block = '".$studRamLabel."' WHERE id = '".$result['id']."'";
                $this->conn->exec($sql);
            }
        }

        $this->setMixblupBlockValuesForAnimals(true);
    }


    private function generateMixblupFiles()
    {
        //Output folder input
        $outputFolderPath = $this->cmdUtil->generateQuestion('Please enter output folder path', $this->outputFolder);

        $this->output->writeln([' ', 'output folder: '.$outputFolderPath, ' ']);

        $isGeneratePedigreeFile = $this->cmdUtil->generateConfirmationQuestion('Generate pedigreefile? (y/n): ');
        $isGenerateExteriorDataFile = $this->cmdUtil->generateConfirmationQuestion('Generate exterior measurements datafile? (y/n): ');
        $isGenerateTestAttributesDataFile = $this->cmdUtil->generateConfirmationQuestion('Generate test attributes datafile? (y/n): ');

        //TODO activate fertility datafile generation when it is necessary
        $isGenerateFertilityDataFile = false;
//        $isGenerateFertilityDataFile = $this->cmdUtil->generateConfirmationQuestion('Generate fertility datafile? (y/n): ');

        $this->cmdUtil->setStartTimeAndPrintIt();

        $this->output->writeln([' ', 'Preparing data... ']);
        $mixBlup = new Mixblup($this->em, $outputFolderPath, self::START_YEAR_MEASUREMENT, self::END_YEAR_MEASUREMENTS, $this->cmdUtil, null, $this->output);
        $this->cmdUtil->printElapsedTime('Time to prepare data');

        $mixBlup->validateMeasurementData();

        if($isGenerateExteriorDataFile || $isGenerateTestAttributesDataFile || $isGenerateFertilityDataFile) {
            $this->output->writeln([' ', 'Generating InstructionFiles... ']);
            $mixBlup->generateInstructionFiles();
            $this->cmdUtil->printElapsedTime('Time to generate instruction files');
        }

        if($isGeneratePedigreeFile) {
            $this->output->writeln([' ', 'Generating PedigreeFile... ']);
            $mixBlup->generatePedigreeFile();
            $this->cmdUtil->printElapsedTime('Time to generate pedigreefile');
        }

        if($isGenerateExteriorDataFile) {
            $this->output->writeln([' ', 'Generating Exterior Measurements DataFiles... ']);
            $mixBlup->generateExteriorMeasurementsDataFiles();
            $this->cmdUtil->printElapsedTime('Time to generate exterior measurements datafiles');
        }

        if($isGenerateTestAttributesDataFile) {
            $this->output->writeln([' ', 'Generating Test Attributes DataFiles... ']);
            $mixBlup->generateTestAttributeMeasurementsDataFiles();
            $this->cmdUtil->printElapsedTime('Time to generate test attributes datafiles');
        }

        if($isGenerateFertilityDataFile) {
            $this->output->writeln([' ', 'Generating Fertility DataFiles... ']);
            $mixBlup->generateFertilityDataFiles();
            $this->cmdUtil->printElapsedTime('Time to generate fertility datafiles');
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function deleteDuplicateMeasurements()
    {
        $measurementsFixer = new MeasurementsFixer($this->em, $this->cmdUtil, $this->output);

        $isFixAndClearDuplicates = $this->cmdUtil->generateConfirmationQuestion('Fix measurements and then Clear ALL duplicate measurements? (y/n): ');
        if ($isFixAndClearDuplicates) {
            $measurementsFixer->removeTimeFromDateTimeInAllMeasurements();
            $measurementsFixer->fixMeasurements(false, $this->mutationsFolder);
            $measurementsFixer->deleteDuplicateMeasurements(false);
        }
    }


    private function generateAnimalIdAndDateStringsInAllMeasurements()
    {
        $isRegenerateOldValues = $this->cmdUtil->generateConfirmationQuestion('Also regenerate filled values? (y/n): ');
        $this->cmdUtil->setStartTimeAndPrintIt();
        $count = MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, $isRegenerateOldValues);
        $this->output->writeln('Values generated: '.$count);
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }

}
