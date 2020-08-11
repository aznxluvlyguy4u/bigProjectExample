<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Entity\ResultTableBreedGrades;
use AppBundle\Enumerator\SuccessIndicator;
use AppBundle\Service\AwsExternalTestQueueService;
use AppBundle\Service\AwsInternalTestQueueService;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
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
    /** @var Logger */
    private $logger;
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
        $this->logger = $this->getContainer()->get('logger');
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
            '2: Get sql query for locations ordered by highest current livestock count and then unassigned tags count', "\n",
            '3: Delete animal and all related records', "\n",
            '4: Purge worker test queues', "\n",
            '5: Get uln test data', "\n",
            '6: Find animals with most breedValues', "\n",
            'DEFAULT: Custom test', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1:
                $results = $this->locationRepository->findLocationsWithHighestAnimalCount();
                $this->cmdUtil->writeln($results);
                break;
            case 2: self::printSqlQueryLocationsOrderedByHighestCurrentLivestockCountAndThenUnassignedTagsCount(); break;
            case 3:
                if($this->isBlockedDatabase()) { $this->printDatabaseError(); break; }
                $this->getContainer()->get('app.datafix.animals.exterminator')->deleteAnimalsByCliInput($this->cmdUtil);
                break;
            case 4:
                $purgeCount = $this->getContainer()->get(AwsExternalTestQueueService::class)->purgeQueue();
                $this->cmdUtil->writeln('External test queue messages purged: '.$purgeCount);
                $purgeCount = $this->getContainer()->get(AwsInternalTestQueueService::class)->purgeQueue();
                $this->cmdUtil->writeln('Internal test queue messages purged: '.$purgeCount);
                break;
            case 5: $this->getUlnTestData(); break;
            case 6: $this->getBreedValuesRankingData(); break;
            default:
                $this->customTest();
                break;
        }

        echo "\r\n\r\n";
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

        $results = $this->em->getRepository(ResultTableBreedGrades::class)
            ->retrieveAnimalsWithMostBreedValues($limit, $locationId);
        $this->cmdUtil->writeln($results);
    }


    public static function printSqlQueryLocationsOrderedByHighestCurrentLivestockCountAndThenUnassignedTagsCount(): string
    {
        $sql = "SELECT
    animal_count.ubn,
    animal_count.company_name,
    animal_count.count as current_animals_count,
    tags_count.count as unassigned_tags_count
FROM (
         SELECT l.ubn,
                c.company_name,
                COUNT(a.id) as count,
                'animal'    as type
         FROM animal a
                  INNER JOIN location l ON l.id = a.location_id
                  INNER JOIN company c on l.company_id = c.id
         WHERE a.is_alive
           AND a.location_id NOTNULL
           AND l.is_active
           AND c.is_active
         GROUP BY l.ubn, c.company_name
     )animal_count
INNER JOIN (
    SELECT
        l.ubn,
        c.company_name,
        COUNT(t.id) as count,
        'tag' as type
    FROM tag t
        INNER JOIN location l ON l.id = t.location_id
        INNER JOIN company c on l.company_id = c.id
    WHERE t.tag_status = 'UNASSIGNED'
        AND l.is_active AND c.is_active
    GROUP BY l.ubn, c.company_name
    )tags_count ON tags_count.ubn = animal_count.ubn
ORDER BY animal_count.count DESC, tags_count.count DESC";
        echo $sql;
        return $sql;
    }
}
