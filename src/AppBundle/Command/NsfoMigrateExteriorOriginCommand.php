<?php

namespace AppBundle\Command;

use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
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
        $conn->getConfiguration()->setSQLLogger(null);
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;

        $this->cmdUtil->generateTitle(self::TITLE);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: import version 2016Aug', "\n",
            '2: file 20161007_1156_Stamboekinspectietabel.csv', "\n",
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
        $this->output->writeln('Parsing csv...');

        $csv = $this->parseCSV();
        $totalNumberOfRows = sizeof($csv);
        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, 1);

        //TODO
    }


    private function migrateExteriorMeasurementsCsvFile1()
    {
        $startCounter = $this->cmdUtil->generateQuestion('Please enter start row: ', self::DEFAULT_START_ROW);

        $this->output->writeln('Parsing csv...');

        $csv = $this->parseCSV();
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
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                $i++;
                if ($ignoreFirstLine && $i == 1) { continue; }
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $rows;
    }
}
