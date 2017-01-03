<?php

namespace AppBundle\Command;

use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Util\CommandUtil;

class NsfoMigrateExteriorOriginCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrating Exterior from original data source';
    const DEFAULT_START_ROW = 0;
    const DEFAULT_OPTION = 0;

    private $csvParsingOptions;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var Connection */
    private $conn;

    /** @var ObjectManager */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:exterior:origin')
            ->setDescription(self::TITLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var ObjectManager $em */
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        /** @var Connection $conn */
        $this->conn = $this->em->getConnection();
        $this->conn->getConfiguration()->setSQLLogger(null);
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;

        $this->cmdUtil->generateTitle(self::TITLE);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: file 20161007_1156_Stamboekinspectietabel_edited.csv', "\n",
            '2: import version 2016Aug', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);
        
        switch ($option) {
            case 1:
                $this->csvParsingOptions = [
                    'finder_in' => 'app/Resources/imports/vsm2016nov',
                    'finder_name' => '20161007_1156_Stamboekinspectietabel_edited.csv',
                    'ignoreFirstLine' => true];
                $this->migrateExteriorMeasurementsCsvFile2();
                break;

            case 2:
                $this->csvParsingOptions = [
                    'finder_in' => 'app/Resources/imports/',
                    'finder_name' => 'animal_exterior_measurements_migration_update.csv',
                    'ignoreFirstLine' => true];
                $this->migrateExteriorMeasurementsCsvFile1();
                break;

            default:
                $output->writeln('ABORTED');
                return;
        }

        $output->writeln('DONE');
    }


    private function migrateExteriorMeasurementsCsvFile2()
    {
        $startCounter = $this->cmdUtil->generateQuestion('Please enter start row (default = '.self::DEFAULT_START_ROW.')', self::DEFAULT_START_ROW);

        $this->output->writeln([
            $startCounter.' <- chosen',
            'Parsing csv...']);

        $csv = $this->parseCSV(';');
        $totalNumberOfRows = sizeof($csv);

        $this->output->writeln('Create search arrays');

        //TODO
        $inspectorIdsInDbByFullName = $this->createNewInspectorsIfMissingAndReturnLatestInspectorIds($csv);
dump($inspectorIdsInDbByFullName);die;
        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startCounter);

        $inspectors = [];

        $counter = 0;
        for($i = $startCounter; $i < $totalNumberOfRows; $i++) {

            $line = $csv[$i];

            //Rows above 14 are empty

            $vsmId = $line[0];
            $measurementDate = TimeUtil::fillDateStringWithLeadingZeroes($line[1]);
            $kind = $line[2];
            $skull = $line[3];
            $progress = $line[4];
            $muscularity = $line[5];
            $proportion = $line[6];
            $exteriorType = $line[7];
            $legWork = $line[8];
            $fur = $line[9];
            $generalAppearance = $line[10];
            $height = $line[11];
            $breastDepth = $line[12];
            $torsoLength = $line[13];
            $inspectorName = $line[14];

            $inspectors[$inspectorName] = $inspectorName;

//            if($line[1] != '' && $line[1] != null) {
//
//                $name = $line[0];
//                $measurementDate = new \DateTime(StringUtil::changeDateFormatStringFromAmericanToISO($line[1]));
//                $measurementDateStamp = $measurementDate->format('Y-m-d H:i:s');
//                $measurementDate->add(new \DateInterval('P1D'));
//                $nextDayStamp = $measurementDate->format('Y-m-d H:i:s');
//
//                $kind = $line[2];
//                $progress = (float) $line[3];
//                $height = (float) $line[4];
//
//                $message = $i; //defaultMessage
//
//                //TODO
//
//                $this->cmdUtil->advanceProgressBar(1, $message);
//            }

        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        $this->output->writeln("LINES IMPORTED: " . $counter);
    }


    /**
     * @param array $csv
     * @return array
     */
    private function createNewInspectorsIfMissingAndReturnLatestInspectorIds(array $csv)
    {
        $this->output->writeln('Retrieving current inspectorIds by fullName from database ...');
        $inspectorIdsInDbByFullName = $this->getInspectorIdByFullNameSearchArray();

        $this->output->writeln('Getting all the inspectorNames from the csv file ...');
        $inspectors = [];
        foreach ($csv as $record) {
            $inspectors[$record[14]] = $record[14];
        }

        ksort($inspectors);
        //Remove blank inspectors

        foreach (['', ' '] as $blankName) {
            if(array_key_exists($blankName, $inspectors))  { unset($inspectors[$blankName]); }
        }
        
        foreach ($inspectors as $inspector) {
            if(array_key_exists($inspector, $inspectorIdsInDbByFullName)) {
                unset($inspectors[$inspector]);
            }
        }

        $missingInspectorCount = count($inspectors);
        if($missingInspectorCount > 0) {
            $this->output->writeln($missingInspectorCount.' inspectors are new and are going to be created now...');

            /** @var InspectorRepository $repository */
            $repository = $this->em->getRepository(Inspector::class);

            $failedInsertCount = 0;
            foreach ($inspectors as $inspectorName) {
                $firstName = '';
                $lastName = $inspectorName;
                $isInsertSuccessful = $repository->insertNewInspector($firstName, $lastName);
                if($isInsertSuccessful) {
                    $this->output->writeln($inspectorName.' : created as inspector');
                } else {
                    $this->output->writeln($inspectorName.' : INSERT FAILED');
                    $failedInsertCount++;
                }
            }

            if($failedInsertCount > 0) {
                $this->output->writeln($failedInsertCount.' : INSERTS FAILED, FIX THIS ISSUE');
                die;
            }

            $inspectorIdsInDbByFullName = $this->getInspectorIdByFullNameSearchArray();
        } else {
            $this->output->writeln('There are no missing inspectors');
        }

        return $inspectorIdsInDbByFullName;
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getInspectorIdByFullNameSearchArray()
    {
        $sql = "SELECT id, TRIM(CONCAT(first_name,' ', last_name)) as full_name
                FROM person WHERE type = 'Inspector'
                ORDER BY full_name ASC";
        $results = $this->conn->query($sql)->fetchAll();

        $inspectorIdsInDbByFullName = [];
        foreach ($results as $result) {
            $inspectorIdsInDbByFullName[$result['full_name']] = $result['id'];
        }

        return $inspectorIdsInDbByFullName;
    }


    private function migrateExteriorMeasurementsCsvFile1()
    {
        $startCounter = $this->cmdUtil->generateQuestion('Please enter start row (default = '.self::DEFAULT_START_ROW.')', self::DEFAULT_START_ROW);

        $this->output->writeln('Parsing csv...');

        $csv = $this->parseCSV(',');
        $totalNumberOfRows = sizeof($csv);
        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startCounter);

        $counter = 0;
        for($i = $startCounter; $i < $totalNumberOfRows; $i++) {

            $line = $csv[$i];

            if($line[1] != '' && $line[1] != null) {

                $name = $line[0];
                $measurementDate = new \DateTime(StringUtil::changeDateFormatStringFromAmericanToISO($line[1]));
                $measurementDateStamp = $measurementDate->format('Y-m-d H:i:s');
                $measurementDate->add(new \DateInterval('P1D'));
                $nextDayStamp = $measurementDate->format('Y-m-d H:i:s');

                $kind = $line[2];
                $progress = (float) $line[3];
                $height = (float) $line[4];

                $message = $i; //defaultMessage
                if(NullChecker::isNotNull($measurementDate)){
                    $sql = "SELECT exterior.id as id FROM exterior  INNER JOIN measurement ON exterior.id = measurement.id INNER JOIN animal ON exterior.animal_id = animal.id WHERE animal.name = '".$name."' AND measurement.measurement_date BETWEEN '".$measurementDateStamp."' AND '".$nextDayStamp."'";
                    $result = $this->conn->query($sql)->fetch();

                    if ($result) {
                        if($result['id'] != '') {
                            $sql = "UPDATE exterior SET height = '".$height."', progress = '".$progress."', kind = '".$kind."' WHERE exterior.id = '".$result['id']."'";
                            $this->conn->exec($sql);
                            $message = $i . ' +';
                            $counter++;
                        }
                    }
                }

                $this->cmdUtil->advanceProgressBar(1, $message);
            }

        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        $this->output->writeln("LINES IMPORTED: " . $counter);
    }


    private function parseCSV($separator = ';') {
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
            while (($data = fgetcsv($handle, null, $separator)) !== FALSE) {
                $i++;
                if ($ignoreFirstLine && $i == 1) { continue; }
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $rows;
    }
}
