<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Tests\Compiler\A;
use Symfony\Component\Finder\Finder;

class NsfoReadStnCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate PedigreeNumbers (STN)';
    const DEFAULT_START_ROW = 0;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'animal_table_migration.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:read:stn')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csv = $this->parseCSV();
        $totalNumberOfRows = sizeof($csv);
        /**
         * @var EntityManager $em
         */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);
        $counter = 0;

        $startCounter = $cmdUtil->generateQuestion('Please enter start row: ', self::DEFAULT_START_ROW);

        $cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startCounter);

        for($i = $startCounter; $i < $totalNumberOfRows; $i++) {

            $line = $csv[$i];

            $animalName = $line[0];
            $pieces = explode(" ", $line[1]);

            if(sizeof($pieces) > 1) {
                $pedigreeCountryCode = $pieces[0];
                $pedigreeNumber = str_replace("-", "", $pieces[1]);
            } else {
                $pedigreeCountryCode = substr($pieces[0], 0, 2);
                $pedigreeNumber = substr($pieces[0], 2);
            }

            $sql = "UPDATE animal SET pedigree_country_code = '". $pedigreeCountryCode ."', pedigree_number = '". $pedigreeNumber ."' WHERE name = '". $animalName ."'";
            $em->getConnection()->exec($sql);

            $counter++;
//            $cmdUtil->advanceProgressBar(1, "LINES IMPORTED: " . $counter.'  |  '."TOTAL LINES: " .$totalNumberOfRows);
            $cmdUtil->advanceProgressBar(1);
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
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
            while (($data = fgetcsv($handle, 30, ";")) !== FALSE) {
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
