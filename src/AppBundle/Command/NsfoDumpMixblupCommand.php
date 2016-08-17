<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Report\Mixblup;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
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
    const DEFAULT_OUTPUT_FOLDER_PATH = '/home/data/JVT/projects/NSFO/MixBlup/dump';

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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        //Output folder input
        $outputFolderPath = $cmdUtil->generateQuestion('Please enter output folder path', self::DEFAULT_OUTPUT_FOLDER_PATH);

        $output->writeln([' ', 'output folder: '.$outputFolderPath, ' ']);

        $isGeneratePedigreeFile = $cmdUtil->generateConfirmationQuestion('Generate pedigreefile? (y/n): ');
        $isGenerateDataFile = $cmdUtil->generateConfirmationQuestion('Generate datafile? (y/n): ');

        $cmdUtil->setStartTimeAndPrintIt();
        
        $output->writeln([' ', 'Preparing data... ']);
        $mixBlup = new Mixblup($em, $outputFolderPath, self::INSTRUCTIONS_FILENAME, self::DATA_FILENAME, self::PEDIGREE_FILENAME, self::START_YEAR_MEASUREMENT, self::END_YEAR_MEASUREMENTS);
        $cmdUtil->printElapsedTime('Time to prepare data');

        if($isGenerateDataFile) {
            $output->writeln([' ', 'Generating InstructionFiles... ']);
            $mixBlup->generateInstructionFiles();
            $cmdUtil->printElapsedTime('Time to generate instruction files');
        }

        if($isGeneratePedigreeFile) {
            $output->writeln([' ', 'Generating PedigreeFile... ']);
            $mixBlup->generatePedigreeFile();
            $cmdUtil->printElapsedTime('Time to generate pedigreefile');
        }

        if($isGenerateDataFile) {
            $output->writeln([' ', 'Generating DataFiles... ']);
            $mixBlup->generateDataFiles();
            $cmdUtil->printElapsedTime('Time to generate datafiles');
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

}
