<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
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
        /**
         * @var EntityManager $em
         */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;

        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        $isOnlyCheckPedigreeFormattingInDb = $cmdUtil->generateConfirmationQuestion('Check pedigreeNumber formats in database? (y/n)');
        if($isOnlyCheckPedigreeFormattingInDb) {
            $this->checkPedigreeNumbers();

            $isDeleteIncorrectPedigreeCodes = $cmdUtil->generateConfirmationQuestion('Delete these numbers? (y/n)');
            if($isDeleteIncorrectPedigreeCodes) {
                $this->clearIncorrectPedigreeNumbers();
            }
            die;
        }

        $output->writeln('Reading csv file...');

        $csv = $this->parseCSV();
        $totalNumberOfRows = sizeof($csv);

        $outputFolder = $this->getContainer()->get('kernel')->getRootDir().'/Resources/outputs';
        NullChecker::createFolderPathIfNull($outputFolder);

        $errorOutputFileWrongLength = $outputFolder.'/possible_incorrect_pedigree_codes_wrong_length.csv';
        $errorOutputFileNoDashes = $outputFolder.'/possible_incorrect_pedigree_codes_no_dashes.csv';


        $output->writeln('Find animals with correct pedigree codes in the database...');
        $vsmIdsThatHaveCorrectPedigreeNumbers = $this->findVsmIdsWithCorrectPedigreeNumbersInTheDatabase();
        $output->writeln('Find animals with empty pedigree codes in the database...');
        $vsmIdsThatDontHavePedigreeNumbers = $this->findVsmIdsWithEmptyPedigreeNumbersInTheDatabase();

        $goodFormatCounter = 0;
        $allRowsCounter = 0;

        $isClearPedigreeCodes = $cmdUtil->generateConfirmationQuestion('Delete all old pedigree numbers and country codes? (y/n)');

//        //TODO Include incorrect stns with fixed formatting?
//        $isIncludeIncorrectStns = $cmdUtil->generateConfirmationQuestion('Erase pedigree numbers with incorrect formatting (update with fix later)? (y/n)');

        $startCounter = $cmdUtil->generateQuestion('Please enter start row for importing Pedigree data: ', self::DEFAULT_START_ROW);


        if($isClearPedigreeCodes) {
            $cmdUtil->setStartTimeAndPrintIt(602498, 1, 'Deleting old pedigree country codes and numbers...');
            $this->clearAllPedigreeNumbers();
            $cmdUtil->setProgressBarMessage('Old pedigree data cleared!');
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        }


        $oldIncorrectPedigreesCleared = 0;
        $newGoodPedigreesInserted = 0;
        $output->writeln('=== IMPORTING PEDIGREE DATA ===');
        $cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startCounter);
        for($i = $startCounter; $i < $totalNumberOfRows; $i++) {

            $line = $csv[$i];

            $animalName = $line[0];
            $pieces = explode(" ", $line[1]);

            if($vsmIdsThatHaveCorrectPedigreeNumbers->contains($animalName)) {
                $goodFormatCounter++;
            } else {


                //First check if there are more than 2 pieces to prevent loss of data
                if(count($pieces) > 2) {
                    file_put_contents($errorOutputFileNoDashes, $line[0].';'.$line[1]."\n", FILE_APPEND);
                    //TODO include with correct formatting?
                    if(!$vsmIdsThatDontHavePedigreeNumbers->contains($animalName)) {
                        $this->clearPedigreeNumber($animalName);
                        $oldIncorrectPedigreesCleared++;
                    }

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

                        if(!$vsmIdsThatHaveCorrectPedigreeNumbers->contains($animalName)) {

                            $pedigreeNumber = strtoupper($pedigreeNumber);
                            $sql = "UPDATE animal SET pedigree_country_code = '". $pedigreeCountryCode ."', pedigree_number = '". $pedigreeNumber ."' WHERE name = '". $animalName ."'";
                            $em->getConnection()->exec($sql);

                            $newGoodPedigreesInserted++;
                            $goodFormatCounter++;
                        }
                        //ELSE DO NOTHING

                    } elseif (strpos($pedigreeNumber, '-') != false) {
                        file_put_contents($errorOutputFileWrongLength, $line[0] . ';' . $line[1] . "\n", FILE_APPEND);
                        //TODO Include with corrrect formatting?
                        if(!$vsmIdsThatDontHavePedigreeNumbers->contains($animalName)) {
                            $this->clearPedigreeNumber($animalName);
                            $oldIncorrectPedigreesCleared++;
                        }

                    } else {
                        if(!$vsmIdsThatDontHavePedigreeNumbers->contains($animalName)) {
                            $this->clearPedigreeNumber($animalName);
                            $oldIncorrectPedigreesCleared++;
                        }
                    }
                }
            }



            $allRowsCounter++;
            //                    $cmdUtil->advanceProgressBar(1);
            $cmdUtil->advanceProgressBar(1, 'GOOD FORMATS! : ' . $goodFormatCounter.'| TOTAL LINES PROCESSED: '.$allRowsCounter.'/'.$totalNumberOfRows.' | new pedigrees inserted: '.$newGoodPedigreesInserted .' | incorrect pedigrees deleted: '.$oldIncorrectPedigreesCleared);
        }
        $cmdUtil->setProgressBarMessage('Pedigree data imported! Lines processed: '.$goodFormatCounter);
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
     * @param string $vsmId
     */
    private function clearPedigreeNumber($vsmId)
    {
        $sql = "UPDATE animal SET pedigree_country_code = NULL, pedigree_number = NULL WHERE name = '". $vsmId ."'";
        $this->em->getConnection()->exec($sql);
    }


    /**
     *
     */
    private function checkPedigreeNumbers()
    {
        $sql = "SELECT pedigree_number FROM animal WHERE pedigree_number IS NOT NULL";
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        $count = 0;
        foreach ($results as $result) {
            $pedigreeNumber = Utils::getNullCheckedArrayValue('pedigree_number', $result);
            $isValid = Validator::verifyPedigreeNumberFormat($pedigreeNumber);
            if(!$isValid) {
                dump($pedigreeNumber);
                $count++;
            }
        }

        dump($count);
    }


    /**
     *
     */
    private function clearIncorrectPedigreeNumbers()
    {
        $sql = "SELECT pedigree_number, name FROM animal WHERE pedigree_number IS NOT NULL";
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        $count = 0;
        foreach ($results as $result) {
            $pedigreeNumber = Utils::getNullCheckedArrayValue('pedigree_number', $result);
            $isValid = Validator::verifyPedigreeNumberFormat($pedigreeNumber);
            if(!$isValid) {
                dump('Pedigree of following vsmId deleted: '.$result['name']);
                $this->clearPedigreeNumber($result['name']);
                $count++;
            }
        }

        dump($count);
    }


    /**
     * @return ArrayCollection
     */
    private function findVsmIdsWithCorrectPedigreeNumbersInTheDatabase()
    {

        $sql = "SELECT name, pedigree_number FROM animal WHERE pedigree_number IS NOT NULL";
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        $vsmIds = new ArrayCollection();
        foreach ($results as $result) {
            $pedigreeNumber = $result['pedigree_number'];
            $isValid = Validator::verifyPedigreeNumberFormat($pedigreeNumber);
            if($isValid) {
                $vsmIds->add($result['name']);
            }
        }

        return $vsmIds;
    }


    /**
     * @return ArrayCollection
     */
    private function findVsmIdsWithEmptyPedigreeNumbersInTheDatabase()
    {

        $sql = "SELECT name FROM animal WHERE pedigree_number IS NULL";
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        $vsmIds = new ArrayCollection();
        foreach ($results as $result) {
            $vsmIds->add($result['name']);
        }
        return $vsmIds;
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
