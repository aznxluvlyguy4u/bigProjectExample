<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\HealthCheckTask;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Entity\ResultTableBreedGrades;
use AppBundle\Service\AwsExternalTestQueueService;
use AppBundle\Service\AwsInternalTestQueueService;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
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

        $location = $this->em->getRepository(Location::class)->find(97);
        $task = new HealthCheckTask();
        $task->setUlnCountryCode('NL')
            ->setUlnNumber('100003608834')
            ->setDestinationLocation($location)
            ;

        $animal = $this->em->getRepository(Animal::class)->findByHealthCheckTask($task);
        dump('ANIMAL COUNT '.count($animal));die;


        dump('HEYEYEYEYEY');die;

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Find locations with highest animal count', "\n",
            '2: Delete animal and all related records', "\n",
            '3: Purge worker test queues', "\n",
            '4: Get uln test data', "\n",
            '5: Find animals with most breedValues', "\n",
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
                $purgeCount = $this->getContainer()->get(AwsExternalTestQueueService::class)->purgeQueue();
                $this->cmdUtil->writeln('External test queue messages purged: '.$purgeCount);
                $purgeCount = $this->getContainer()->get(AwsInternalTestQueueService::class)->purgeQueue();
                $this->cmdUtil->writeln('Internal test queue messages purged: '.$purgeCount);
                break;
            case 4: $this->getUlnTestData(); break;
            case 5: $this->getBreedValuesRankingData(); break;
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


    private function getBreedValuesRankingData()
    {
        $limit = $this->cmdUtil->questionForIntChoice(10, 'Result count');
        $locationId = $this->cmdUtil->questionForIntChoice(0, 'locationId (0 = all locations)');

//        $locationId = abs(intval($this->cmdUtil->questionForIntChoice('LocationId (0 = all locations)', 0)));
        $results = $this->em->getRepository(ResultTableBreedGrades::class)
            ->retrieveAnimalsWithMostBreedValues($limit, $locationId);
        $this->cmdUtil->writeln($results);
    }
}
