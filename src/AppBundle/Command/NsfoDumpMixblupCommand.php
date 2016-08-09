<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
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
    const DATA_FILENAME = 'data.txt';
    const PEDIGREE_FILENAME = 'afstamming.txt';
    const INSTRUCTIONS_FILENAME = 'mixblup_instructions.inp';
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

        //includes retrieving animals, might take a very long time
        $mixBlup = new Mixblup($em, $outputFolderPath, self::INSTRUCTIONS_FILENAME, self::DATA_FILENAME, self::PEDIGREE_FILENAME);

        //Generate InstructionFile
        $instructionFilePath = $mixBlup->generateInstructionFile();
        $pedigreeFilePath = $mixBlup->generatePedigreeFile();
        $dataFilePath = $mixBlup->generateDataFile();

        $output->writeln([' ', 'output folder: '.$outputFolderPath, ' ']);

        $output->writeln('=== FINISHED ===');
    }

}
