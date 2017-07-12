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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoTestCommand extends ContainerAwareCommand
{
    const TITLE = 'TESTING';
    const INPUT_PATH = '/path/to/file.txt';
    const OUTPUT_FOLDER_NAME = '/Resources/outputs/test';
    const FILENAME = 'test.csv';
    const DEFAULT_OPTION = 0;

    const CREATE_TEST_FOLDER_IF_NULL = true;

    /** @var ObjectManager $em */
    private $em;

    /** @var Connection $conn */
    private $conn;

    /** @var string */
    private $rootDir;

    /** @var LocationRepository */
    private $locationRepository;

    /** @var AnimalRepository */
    private $animalRepository;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var string */
    private $databaseName;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/',
        'finder_name' => 'filename.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:test')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
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
            '1: Custom test', "\n",
            '2: Custom test', "\n",
            'DEFAULT: Custom test', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1:
                //PLACEHOLDER
                $this->customTest();
                break;
            case 2:
                //PLACEHOLDER
                $this->customTest();
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


    private function parseCSV() {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];

        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($this->csvParsingOptions['finder_name'])
        ;
        foreach ($finder as $file) { $csv = $file; }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
                $i++;
                if ($ignoreFirstLine && $i == 1) { continue; }
                $rows[] = $data;
                gc_collect_cycles();
            }
            fclose($handle);
        }

        return $rows;
    }

}
