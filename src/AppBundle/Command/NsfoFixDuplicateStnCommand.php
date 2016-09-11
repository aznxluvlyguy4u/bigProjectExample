<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoFixDuplicateStnCommand extends ContainerAwareCommand
{
    const TITLE = 'Fix Duplicate PedigreeNumbers (STN)';
    const DEFAULT_START_ROW = 0;
    const DEFAULT_OPTION = 0;

    const DUPLICATE_IN_SOURCE_FILENAME = 'duplicate_pedigree_codes_in_source_file.csv';
    const DUPLICATE_IN_DATABASE_FILENAME = 'duplicate_pedigree_codes_in_database.csv';

    /** @var ObjectManager */
    private $em;

    /** @var array */
    private $csv;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var String */
    private $outputFolder;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'animal_table_migration_v2.csv',
        'ignoreFirstLine' => true
    );
    
    protected function configure()
    {
        $this
            ->setName('nsfo:fix:duplicate:stn')
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
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;

        $this->outputFolder = $this->getContainer()->get('kernel')->getRootDir().'/Resources/outputs';
        NullChecker::createFolderPathIfNull($this->outputFolder);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ',"\n",
            '1: Check for duplicate pedigree numbers in source file',"\n",
            '2: Find duplicate pedigree numbers in database, and write to file',"\n",
            '3: Fix (CLEAR) duplicate pedigree numbers in database',"\n",
            'abort (other)',"\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $output->writeln('Reading csv file...');
                $this->csv = $this->parseCSV();

                $this->checkForDuplicatePedigreeNumbersInSourceFile();
                break;

            case 2:
                $this->writeToFileDuplicatePedigreeNumbersInDatabase();
                break;

            case 3:
                $this->fixDuplicatePedigreeNumbersInDatabase();
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }


    }


    private function writeToFileDuplicatePedigreeNumbersInDatabase()
    {
        $duplicateOutputFolder = $this->outputFolder.'/'.self::DUPLICATE_IN_DATABASE_FILENAME;

        $results = $this->getDuplicatePedigreeCodesInDatabase();
        $this->output->writeln('Total duplicate of pedigreeCountryCode AND pedigreeNumber in database: '.count($results));

        foreach ($results as $result) {
            $id = Utils::getNullCheckedArrayValue('id', $result);
            $vsmId = Utils::getNullCheckedArrayValue('name', $result);
            $pedigreeNumber = Utils::getNullCheckedArrayValue('pedigree_number', $result);
            $pedigreeCountryCode = Utils::getNullCheckedArrayValue('pedigree_country_code', $result);

            $row = $id.';'.$vsmId.';'.$pedigreeCountryCode.';'.$pedigreeNumber.';'.$pedigreeCountryCode.' '.$pedigreeNumber;
            file_put_contents($duplicateOutputFolder, $row."\n", FILE_APPEND);
        }
    }


    /**
     * Returning animal data for animals for which BOTH the pedigreeCountryCode and pedigreeNumber are identical
     *
     * @return array
     */
    private function getDuplicatePedigreeCodesInDatabase()
    {
        $sql = "SELECT a.id, a.name, a.pedigree_number, a.pedigree_country_code FROM animal a INNER JOIN (SELECT pedigree_country_code, pedigree_number, COUNT(*) FROM animal GROUP BY pedigree_country_code, pedigree_number HAVING COUNT(*) > 1) dc ON a.pedigree_number = dc.pedigree_number";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        
        return $results;
    }
    

    private function fixDuplicatePedigreeNumbersInDatabase()
    {
        $results = $this->getDuplicatePedigreeCodesInDatabase();
        $this->output->writeln('Total duplicate of pedigreeCountryCode AND pedigreeNumber in database: '.count($results));

        $isEraseDuplicatePedigreeCodes = $this->cmdUtil->generateConfirmationQuestion('CLEAR ALL Duplicate pedigreeCountryCodes and pedigreeNumbers combinations? (y/n)');

        if($isEraseDuplicatePedigreeCodes) {
            foreach ($results as $result) {
                $vsmId = Utils::getNullCheckedArrayValue('name', $result);
                $this->clearPedigreeNumber($vsmId);
            }
        }
    }

    /**
     * @param string $vsmId
     */
    private function clearPedigreeNumber($vsmId)
    {
        $sql = "UPDATE animal SET pedigree_country_code = NULL, pedigree_number = NULL WHERE name = '". $vsmId ."'";
        $this->em->getConnection()->exec($sql);
    }


    
    private function checkForDuplicatePedigreeNumbersInSourceFile()
    {
        $duplicateOutputFolder = $this->outputFolder.'/'.self::DUPLICATE_IN_SOURCE_FILENAME;

        $foundPedigreeCodes = array();
        $duplicates = array();
        $totalValidPedigreeNumbers = 0;
        $totalNumberOfRows = sizeof($this->csv);

        foreach ($this->csv as $line)
        {
            $vsmId = $line[0];
            $pieces = explode(" ", $line[1]);
            $pedigreeData = $this->getPedigreeData($pieces);

            $pedigreeCountryCode = $pedigreeData->get(JsonInputConstant::PEDIGREE_COUNTRY_CODE);
            $pedigreeNumber = $pedigreeData->get(JsonInputConstant::PEDIGREE_NUMBER);
            $pedigreeCode = $pedigreeCountryCode.' '.$pedigreeNumber;
            $isValidPedigreeNumber = $pedigreeData->get(Constant::IS_VALID_NAMESPACE);

            if($isValidPedigreeNumber) {
                $totalValidPedigreeNumbers++;
                if(array_key_exists($pedigreeCode, $foundPedigreeCodes)) {
                    $duplicates[$foundPedigreeCodes[$pedigreeCode]] = $pedigreeCode;
                    $duplicates[$vsmId] = $pedigreeCode;
                }

                $foundPedigreeCodes[$pedigreeCode] = $vsmId;
            }
        }
        $this->output->writeln('Total rows: '.$totalNumberOfRows);
        $this->output->writeln('Total valid pedigree numbers: '.$totalValidPedigreeNumbers);
        $this->output->writeln('Duplicates in source file: '. count($duplicates));

        $vsmIdsOfDuplicates = array_keys($duplicates);
        foreach ($vsmIdsOfDuplicates as $vsmIdsOfDuplicate)
        {
            $pedigreeCode = $duplicates[$vsmIdsOfDuplicate];
            file_put_contents($duplicateOutputFolder, $vsmIdsOfDuplicate.';'.$pedigreeCode."\n", FILE_APPEND);
        }


    }


    /**
     * @param array $pieces
     * @return ArrayCollection
     */
    private function getPedigreeData($pieces)
    {
        //Initialize default values
        $pedigreeNumber = null;
        $pedigreeCountryCode = null;
        $isValidPedigreeNumber = false;

        $result = new ArrayCollection();
        $result->set(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $pedigreeCountryCode);
        $result->set(JsonInputConstant::PEDIGREE_NUMBER, $pedigreeNumber);
        $result->set(Constant::IS_VALID_NAMESPACE, $isValidPedigreeNumber);

        //Null check
        if(count($pieces) == 0) {
            return $result;
        }

        
        //First check if there are more than 2 pieces to prevent loss of data
        if(count($pieces) > 2) {
            //Skip these due to incorrect formatting

        } else {
            if(sizeof($pieces) == 2) {
                $pedigreeCountryCode = $pieces[0];
                $pedigreeNumber = strtoupper($pieces[1]);

            } else {
                //only one piece, so country code and number are concatenated
                $pedigreeCountryCode = substr($pieces[0], 0, 2);
                $pedigreeNumber = strtoupper(substr($pieces[0], 2));
            }

            $isValidPedigreeNumber = strpos($pedigreeNumber, '-') == 5 && strlen($pedigreeNumber) == 11;
        }
        $result->set(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $pedigreeCountryCode);
        $result->set(JsonInputConstant::PEDIGREE_NUMBER, $pedigreeNumber);
        $result->set(Constant::IS_VALID_NAMESPACE, $isValidPedigreeNumber);
        return $result;
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

