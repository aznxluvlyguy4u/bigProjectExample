<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
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
    const DEFAULT_TEST_ANIMAL_LIMIT = 300;
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
            '3: Purge worker test queues', "\n",
            '4: Get uln test data', "\n",
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
            case 3:
                $purgeCount = $this->getContainer()->get('app.aws.queueservice.external.test')->purgeQueue();
                $this->cmdUtil->writeln('External test queue messages purged: '.$purgeCount);
                $purgeCount = $this->getContainer()->get('app.aws.queueservice.internal.test')->purgeQueue();
                $this->cmdUtil->writeln('Internal test queue messages purged: '.$purgeCount);
                break;
            case 4: $this->getUlnTestData(); break;
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

        $selectQuery = "SELECT * FROM animal LIMIT 10";

        SqlUtil::writeToFile(
            $this->conn,
            $selectQuery,
            '/home/code/jvt/nsfo/api/var/cache/dev/csv/dieren_overzicht_rapportage__peildatum_2018-02-03__gemaakt_op_2018-04-09_14u29m05s.csv',
            null);

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


    private function getUlnTestData()
    {
        do {
            $option = $this->cmdUtil->generateMultiLineQuestion([
                'Choose test animal limit: (default = '.self::DEFAULT_TEST_ANIMAL_LIMIT.')', "\n",
            ], self::DEFAULT_TEST_ANIMAL_LIMIT);
        } while(!is_int($option) && !ctype_digit($option));

        $sql = "SELECT uln_country_code, uln_number FROM animal LIMIT ".$option;
        $results = $this->conn->query($sql)->fetchAll();

        $string = '';
        $prefix = '';
        foreach ($results as $result) {
            $string .= $prefix."{\"uln_country_code\":\"".$result['uln_country_code']."\",".
                "\"uln_number\":\"".$result['uln_number']."\"}";
            $prefix = ',';
        }
        $this->output->writeln($string);
    }
}
