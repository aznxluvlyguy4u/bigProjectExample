<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Migration\AnimalExterminator;
use AppBundle\Util\CommandUtil;

use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoTestCommand extends ContainerAwareCommand
{
    const TITLE = 'TESTING';
    const INPUT_PATH = '/path/to/file.txt';
    const OUTPUT_FOLDER_NAME = '/Resources/outputs/test';
    const FILENAME = 'test.csv';
    const DEFAULT_OPTION = 0;
    const BLOCKED_DATABASE_NAME_PART = 'prod';

    const CREATE_TEST_FOLDER_IF_NULL = true;

    /** @var ObjectManager $em */
    private $em;
    /** @var Connection $conn */
    private $conn;
    /** @var OutputInterface */
    private $output;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var string */
    private $rootDir;
    /** @var LocationRepository */
    private $locationRepository;
    /** @var AnimalRepository */
    private $animalRepository;
    /** @var string */
    private $databaseName;


    protected function configure()
    {
        $this
            ->setName('nsfo:test')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager|EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->output = $output;
        $this->conn = $em->getConnection();
        $this->rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        if(self::CREATE_TEST_FOLDER_IF_NULL) { NullChecker::createFolderPathIfNull($this->rootDir.self::OUTPUT_FOLDER_NAME); }
        $this->locationRepository = $em->getRepository(Location::class);
        $this->animalRepository = $em->getRepository(Animal::class);
        $this->databaseName = $this->conn->getDatabase();
        

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln([DoctrineUtil::getDatabaseHostAndNameString($em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Find locations with highest animal count', "\n",
            '2: Delete animal and all related records', "\n",
            'DEFAULT: Custom test', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1:
                $results = $this->locationRepository->findLocationsWithHighestAnimalCount();
                $this->cmdUtil->writeln($results);
                break;
            case 2:
                if($this->isBlockedDatabase()) { $this->printDatabaseError(); break; }
                $this->getContainer()->get('app.datafix.animals.exterminator')->deleteAnimalsByCliInput($this->cmdUtil);
                break;
            default:
                $this->customTest();
                break;
        }
        $output->writeln('DONE');
        
        
    }


    private function customTest()
    {
        /*
         * Insert your custom test here
         */
    }


    private function printDatabaseError()
    {
        $this->output->writeln('THIS COMMAND IS NOT ALLOWED FOR ANY DATABASE '.
            "WHICH NAME CONTAINS '".self::BLOCKED_DATABASE_NAME_PART."'!");
    }


    /**
     * @return bool
     */
    private function isBlockedDatabase()
    {
        return StringUtil::isStringContains(strtolower($this->databaseName), self::BLOCKED_DATABASE_NAME_PART);
    }
}
