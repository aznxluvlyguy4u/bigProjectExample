<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Report\Mixblup;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
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
    const DEFAULT_OUTPUT_FOLDER_PATH = '/home/data/JVT/projects/NSFO/FEATURES/MixBlup/dump';
    const DEFAULT_OPTION = 0;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var EntityManager */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:dump:mixblup')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));


        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate Mixblup !BLOCKs', "\n",
            '2: Generate Mixblup Datafile', "\n",
            '3: Clear all Mixblup !BLOCK values', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $this->generateMixblupBlocks();
                break;

            case 2:
                $this->generateMixblupFiles();
                break;

            case 3:
                $this->clearAllMixblupBlockValues();
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
        $sql = "UPDATE animal SET mixblup_block = uln_country_code WHERE mixblup_block IS NULL AND uln_country_code IS NOT NULL AND uln_country_code <> 'NL' AND ".$typeSelection;
        $this->em->getConnection()->exec($sql);

        //Non-Export Animals with ubn of birth
        $sql = "UPDATE animal SET mixblup_block = ubn_of_birth WHERE mixblup_block IS NULL AND animal.ubn_of_birth IS NOT NULL AND ".$typeSelection;
        $this->em->getConnection()->exec($sql);

        //If no ubn of birth is available, then take the ubn of the current location
        $sql = "SELECT a.id as id, l.ubn as ubn FROM animal a INNER JOIN location l ON (l.id = a.location_id) WHERE a.mixblup_block IS NULL AND a.location_id IS NOT NULL AND ".$typeSelection;
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        foreach ($results as $result) {
            $blockValue = str_replace(' ','', $result['ubn']).'_C'; //remove spaces and append with _C
            $sql = "UPDATE animal SET mixblup_block = '".$blockValue."' WHERE id = ".$result['id'];
            $this->em->getConnection()->exec($sql);
        }

        $sql = "UPDATE animal SET mixblup_block = 'UNKNOWN' WHERE mixblup_block IS NULL AND ".$typeSelection;
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
        $studRamLabel = 'STUD_RAM';
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
        $outputFolderPath = $this->cmdUtil->generateQuestion('Please enter output folder path', self::DEFAULT_OUTPUT_FOLDER_PATH);

        $this->output->writeln([' ', 'output folder: '.$outputFolderPath, ' ']);

        $isGeneratePedigreeFile = $this->cmdUtil->generateConfirmationQuestion('Generate pedigreefile? (y/n): ');
        $isGenerateDataFile = $this->cmdUtil->generateConfirmationQuestion('Generate datafile? (y/n): ');

        $this->cmdUtil->setStartTimeAndPrintIt();

        $this->output->writeln([' ', 'Preparing data... ']);
        $mixBlup = new Mixblup($this->em, $outputFolderPath, self::INSTRUCTIONS_FILENAME, self::DATA_FILENAME, self::PEDIGREE_FILENAME, self::START_YEAR_MEASUREMENT, self::END_YEAR_MEASUREMENTS);
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
}
