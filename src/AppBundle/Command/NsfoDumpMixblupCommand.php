<?php

namespace AppBundle\Command;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\TailLengthRepository;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Report\Mixblup;
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoDumpMixblupCommand extends ContainerAwareCommand
{
    const TITLE = 'Generate NSFO MiXBLUP files';
    const DATA_FILENAME = 'databestand';
    const PEDIGREE_FILENAME = 'afstamming';
    const INSTRUCTIONS_FILENAME = 'mixblup_instructions';
    const START_YEAR_MEASUREMENT = 2014;
    const END_YEAR_MEASUREMENTS = 2016;
    const DEFAULT_OUTPUT_FOLDER_PATH = '/Resources/outputs/mixblup';
    const DEFAULT_MUTATIONS_FOLDER_PATH = '/Resources/mutations';
    const DEFAULT_OPTION = 0;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var ObjectManager */
    private $em;
    
    /** @var ExteriorRepository $exteriorRepository */
    private $exteriorRepository;

    /** @var WeightRepository $weightRepository */
    private $weightRepository;

    /** @var TailLengthRepository $tailLengthRepository */
    private $tailLengthRepository;
    
    /** @var MuscleThicknessRepository */
    private $muscleThicknessRepository;
    
    /** @var BodyFatRepository */
    private $bodyFatRepository;

    /** @var string */
    private $outputFolder;

    /** @var string */
    private $mutationsFolder;

    
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
        
        $this->exteriorRepository = $this->em->getRepository(Exterior::class);
        $this->weightRepository = $this->em->getRepository(Weight::class);
        $this->tailLengthRepository  = $this->em->getRepository(TailLength::class);
        $this->muscleThicknessRepository = $this->em->getRepository(MuscleThickness::class);
        $this->bodyFatRepository = $this->em->getRepository(BodyFat::class);

        /* Setup folders */
        $this->outputFolder = $this->getContainer()->get('kernel')->getRootDir().self::DEFAULT_OUTPUT_FOLDER_PATH;
        NullChecker::createFolderPathIfNull($this->outputFolder);
        $this->mutationsFolder = $this->getContainer()->get('kernel')->getRootDir().self::DEFAULT_MUTATIONS_FOLDER_PATH;
        NullChecker::createFolderPathIfNull($this->mutationsFolder);


        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));


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
            $this->em->getConnection()->exec($sql);
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
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        foreach ($results as $result) {
            $blockValue = $result['country_id']*10;
            $sql = "UPDATE animal SET mixblup_block = '".$blockValue."' WHERE id = ".$result['id'];
            $this->em->getConnection()->exec($sql);
        }

        //Non-Export Animals with ubn of birth
        $sql = "UPDATE animal SET mixblup_block = CAST(ubn_of_birth AS INT) WHERE mixblup_block IS NULL AND animal.ubn_of_birth IS NOT NULL AND animal.ubn_of_birth <> '' AND ".$typeSelection;
        $this->em->getConnection()->exec($sql);

        //If no ubn of birth is available, then take the ubn of the current location
        $sql = "SELECT a.id as id, l.ubn as ubn FROM animal a INNER JOIN location l ON (l.id = a.location_id) WHERE a.mixblup_block IS NULL AND a.location_id IS NOT NULL AND ".$typeSelection;
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        foreach ($results as $result) {
            $blockValue = str_replace(' ','', $result['ubn']); //remove spaces
            $sql = "UPDATE animal SET mixblup_block = '".$blockValue."' WHERE id = ".$result['id'];
            $this->em->getConnection()->exec($sql);
        }

        $sql = "UPDATE animal SET mixblup_block = '2' WHERE mixblup_block IS NULL AND ".$typeSelection;
        $this->em->getConnection()->exec($sql);
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
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        // STUD RAMS
        foreach ($results as $result) {
            if($result['count'] >= $studRamUbnMinimum) {
                $sql = "UPDATE animal SET mixblup_block = '".$studRamLabel."' WHERE id = '".$result['id']."'";
                $this->em->getConnection()->exec($sql);
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
        $isGenerateDataFile = $this->cmdUtil->generateConfirmationQuestion('Generate datafile? (y/n): ');

        $this->cmdUtil->setStartTimeAndPrintIt();

        $this->output->writeln([' ', 'Preparing data... ']);
        $mixBlup = new Mixblup($this->em, $outputFolderPath, self::INSTRUCTIONS_FILENAME, self::DATA_FILENAME, self::PEDIGREE_FILENAME, self::START_YEAR_MEASUREMENT, self::END_YEAR_MEASUREMENTS, $this->cmdUtil);
        $this->cmdUtil->printElapsedTime('Time to prepare data');

        if($isGenerateDataFile) {
            $this->output->writeln([' ', 'Generating InstructionFiles... ']);
            $mixBlup->generateInstructionFiles();
            $this->cmdUtil->printElapsedTime('Time to generate instruction files');
        }

        if($isGeneratePedigreeFile) {
            $this->output->writeln([' ', 'Generating PedigreeFile... ']);
            $mixBlup->generatePedigreeFile();
            $this->cmdUtil->printElapsedTime('Time to generate pedigreefile');
        }

        if($isGenerateDataFile) {
            $this->output->writeln([' ', 'Generating DataFiles... ']);
            $mixBlup->generateDataFiles();
            $this->cmdUtil->printElapsedTime('Time to generate datafiles');
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function deleteDuplicateMeasurements()
    {

        $isRemoveTimeFromMeasurementDates = $this->cmdUtil->generateConfirmationQuestion('Remove time from MeasurementDates? (y/n): ');
        if($isRemoveTimeFromMeasurementDates) {
            $this->weightRepository->removeTimeFromAllMeasurementDates();
        }
        
        $isClearDuplicates = $this->cmdUtil->generateConfirmationQuestion('Fix measurements and then Clear ALL duplicate measurements? (y/n): ');
        if ($isClearDuplicates) {

            $this->cmdUtil->setStartTimeAndPrintIt(4, 1, 'Fixing measurements...');

            $weightFixResult = $this->weightRepository->fixMeasurements();
            $message = $weightFixResult[Constant::MESSAGE_NAMESPACE];
            $this->cmdUtil->advanceProgressBar(1, $message);

            $bodyFatFixResult = $this->bodyFatRepository->fixMeasurements();
            $message = $message .'| '. $bodyFatFixResult[Constant::MESSAGE_NAMESPACE];
            $this->cmdUtil->advanceProgressBar(1, $message);

            $exteriorFixResult = $this->exteriorRepository->fixMeasurements($this->mutationsFolder);
            $message = $message .'| '. $exteriorFixResult[Constant::MESSAGE_NAMESPACE];
            $this->cmdUtil->advanceProgressBar(1, $message);

            $totalDuplicatesDeleted = $weightFixResult[Constant::COUNT] + $bodyFatFixResult[Constant::COUNT]
            + $exteriorFixResult[Constant::COUNT];
            if($totalDuplicatesDeleted == 0) {
                $message =  'No measurements fixed';
                $this->cmdUtil->setProgressBarMessage($message);
            }
            $this->cmdUtil->setEndTimeAndPrintFinalOverview();



            $this->cmdUtil->setStartTimeAndPrintIt(6, 1, 'Deleting duplicate measurements...');

            $exteriorsDeleted = $this->exteriorRepository->deleteDuplicates();
            $message = 'Duplicates deleted, exteriors: ' . $exteriorsDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $weightsDeleted = $this->weightRepository->deleteDuplicates();
            $message = $message . '| weights: ' . $weightsDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $tailLengthsDeleted = $this->tailLengthRepository->deleteDuplicates();
            $message = $message . '| tailLength: ' . $tailLengthsDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $muscleThicknessesDeleted = $this->muscleThicknessRepository->deleteDuplicates();
            $message = $message . '| muscle: ' . $muscleThicknessesDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $bodyFatsDeleted = $this->bodyFatRepository->deleteDuplicates();
            $message = $message . '| BodyFat: ' . $bodyFatsDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $totalDuplicatesDeleted = $exteriorsDeleted + $weightsDeleted + $tailLengthsDeleted + $muscleThicknessesDeleted + $bodyFatsDeleted;
            if($totalDuplicatesDeleted == 0) {
                $message =  'No duplicates deleted';
                $this->cmdUtil->setProgressBarMessage($message);
            }

            $this->cmdUtil->setEndTimeAndPrintFinalOverview();


            //Final overview
            $contradictingWeightsLeft = count($this->weightRepository->getContradictingWeightsForExportFile());
            $contradictingMuscleThicknessesLeft = count($this->muscleThicknessRepository->getContradictingMuscleThicknessesForExportFile());
            $contradictingTailLengthsLeft = count($this->tailLengthRepository->getContradictingTailLengthsForExportFile());
            $contradictingBodyFatsLeft = count($this->bodyFatRepository->getContradictingBodyFatsForExportFile());
            $contradictingExteriorsLeft = count($this->exteriorRepository->getContradictingExteriorsForExportFile());
            $contradictingMeasurementsLeft = $contradictingWeightsLeft + $contradictingMuscleThicknessesLeft + $contradictingTailLengthsLeft + $contradictingExteriorsLeft;

            if($contradictingMeasurementsLeft > 0) {
                $this->output->writeln('=== Contradicting measurements left ===');
                if($contradictingWeightsLeft > 0) { $this->output->writeln('weights: '.$contradictingWeightsLeft); }
                if($contradictingMuscleThicknessesLeft > 0) { $this->output->writeln('muscleThickness: '.$contradictingMuscleThicknessesLeft); }
                if($contradictingTailLengthsLeft > 0) { $this->output->writeln('tailLengths: '.$contradictingTailLengthsLeft); }
                if($contradictingBodyFatsLeft > 0) { $this->output->writeln('bodyFats: '.$contradictingBodyFatsLeft); }
                if($contradictingExteriorsLeft > 0) { $this->output->writeln('exteriors: '.$contradictingExteriorsLeft); }

            } else {
                $this->output->writeln('No contradicting measurements left!');
            }
        }
    }


    private function generateAnimalIdAndDateStringsInAllMeasurements()
    {
        $isRegenerateOldValues = $this->cmdUtil->generateConfirmationQuestion('Also regenerate filled values? (y/n): ');
        $this->cmdUtil->setStartTimeAndPrintIt();
        $count = MeasurementsUtil::generateAnimalIdAndDateValues($this->em, $isRegenerateOldValues);
        $this->output->writeln('Values generated: '.$count);
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }

}
