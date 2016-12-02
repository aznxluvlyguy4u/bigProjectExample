<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;

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

    const CREATE_TEST_FOLDER_IF_NULL = true;

    /** @var ObjectManager $em */
    private $em;

    /** @var Connection $conn */
    private $conn;

    /** @var string */
    private $rootDir;

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
        $cmdUtil = new CommandUtil($input, $output, $helper);
        if(self::CREATE_TEST_FOLDER_IF_NULL) { NullChecker::createFolderPathIfNull($this->rootDir.self::OUTPUT_FOLDER_NAME); }

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $sql = "SELECT uln_country_code, uln_number FROM animal WHERE location_id = 262 AND gender = 'FEMALE'";
        $results = $this->conn->query($sql)->fetchAll();

        $string = '';
        foreach ($results as $result) {
            $string = $string.'{ "uln_country_code": "'.$result['uln_country_code'].'", "uln_number": "'.$result['uln_number'].'"},';

        }

        $output->writeln(['',
            '=== Print Something ===',
            'Result 1: ',
            'Result 2: ',
            $string,
            '']);

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
