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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class NsfoReadStnCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate PedigreeNumbers (STN)';
    const DEFAULT_START_ROW = 0;

    /** @var ObjectManager */
    private $em;


    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'animal_table_migration_v2.csv',
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
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;

        $outputFolder = $this->getContainer()->get('kernel')->getRootDir().'/Resources/outputs';
        $this->nullCheckFolderPath($outputFolder);

        $errorOutputFileWrongLength = $outputFolder.'/possible_incorrect_pedigree_codes_wrong_length.csv';
        $errorOutputFileNoDashes = $outputFolder.'/possible_incorrect_pedigree_codes_no_dashes.csv';

        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);
        $counter = 0;

        $isClearPedigreeCodes = $cmdUtil->generateConfirmationQuestion('Delete all old pedigree numbers and country codes? (y/n)');

        $startCounter = $cmdUtil->generateQuestion('Please enter start row for importing Pedigree data: ', self::DEFAULT_START_ROW);


        $cmdUtil->setStartTimeAndPrintIt(602498, 1, 'Deleting old pedigree country codes and numbers...');
        if($isClearPedigreeCodes) {
            $this->clearAllPedigreeNumbers();
            $cmdUtil->setProgressBarMessage('Old pedigree data cleared!');
        }
        $cmdUtil->setEndTimeAndPrintFinalOverview();


        $output->writeln('=== IMPORTING PEDIGREE DATA ===');
        $cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startCounter);
        for($i = $startCounter; $i < $totalNumberOfRows; $i++) {

            $line = $csv[$i];

            $animalName = $line[0];
            $pieces = explode(" ", $line[1]);

            //First check if there are more than 2 pieces to prevent loss of data
            if(count($pieces) > 2) {
                file_put_contents($errorOutputFileNoDashes, $line[0].';'.$line[1]."\n", FILE_APPEND);
            
            } else {
                if(sizeof($pieces) > 1) {
                    $pedigreeCountryCode = $pieces[0];
                    $pedigreeNumber = $pieces[1];

                } else {
                    $pedigreeCountryCode = substr($pieces[0], 0, 2);
                    $pedigreeNumber = substr($pieces[0], 2);
                }

                $isValidPedigreeNumber = strpos($pedigreeNumber, '-') == 5 && strlen($pedigreeNumber) == 11;

                if($isValidPedigreeNumber) {

                    $sql = "UPDATE animal SET pedigree_country_code = '". $pedigreeCountryCode ."', pedigree_number = '". $pedigreeNumber ."' WHERE name = '". $animalName ."'";
                    $em->getConnection()->exec($sql);

                    $counter++;
                    $cmdUtil->advanceProgressBar(1, "LINES IMPORTED: " . $counter.'  |  '."TOTAL LINES: " .$totalNumberOfRows);

                } elseif (strpos($pedigreeNumber, '-') != false) {
                        file_put_contents($errorOutputFileWrongLength, $line[0] . ';' . $line[1] . "\n", FILE_APPEND);
                }
            }

        }
        $cmdUtil->setProgressBarMessage('Pedigree data imported!'.$counter);
        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function clearAllPedigreeNumbers()
    {
        $sql = "UPDATE animal SET pedigree_country_code = NULL;";
        $this->em->getConnection()->exec($sql);
        $sql = "UPDATE animal SET pedigree_number = NULL;";
        $this->em->getConnection()->exec($sql);
    }


    /**
     * @param string $folderPath
     */
    private function nullCheckFolderPath($folderPath)
    {
        $fs = new Filesystem();
        if(!$fs->exists($folderPath)) {
            $fs->mkdir($folderPath);
        }
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
