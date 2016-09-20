<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Util\CommandUtil;

class NsfoMigrateMusclethicknessCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrating Muscle Thickness';
    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'musclethickness_measurements_migration.csv',
        'ignoreFirstLine' => false
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:musclethickness')
            ->setDescription(self::TITLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csv = $this->parseCSV();
        /**
         * @var ObjectManager $em
         */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);
        $counter = 0;

        $cmdUtil->setStartTimeAndPrintIt();
        foreach ($csv as $line) {
            $ulnCountryCode = $line[0];
            $ulnNumber = $line[1];
            $inspectorId= ($line[2] != null || $line[2] != "" ) ? (int) $line[2] : "";
            $logDate = $line[3];
            $measurementDate = $line[4];

            $muscleThickness = (float) $line[6];

            $counter++;

            if ($counter % 10000 == 0) {
                $output->writeln($counter);
            }

            $sql = "SELECT animal.id FROM animal WHERE uln_country_code = '".$ulnCountryCode."' AND uln_number = '".$ulnNumber."' ORDER BY animal.id DESC LIMIT 1";
            $result = $em->getConnection()->query($sql)->fetch();

            if ($result['id'] != "" || $result['id'] != null) {

                if($inspectorId == "") {
                    $sql = "INSERT INTO measurement (id, log_date, measurement_date, type) VALUES (nextval('measurement_id_seq'),'" .$logDate. "','" . $measurementDate . "','MuscleThickness')";
                    $em->getConnection()->exec($sql);
                } else {
                    $sql = "INSERT INTO measurement (id, inspector_id, log_date, measurement_date, type) VALUES (nextval('measurement_id_seq'),'" . $inspectorId . "','" . $logDate . "','" . $measurementDate . "','MuscleThickness')";
                    $em->getConnection()->exec($sql);
                }

                $sql = "INSERT INTO muscle_thickness (id, animal_id, muscle_thickness) VALUES (currval('measurement_id_seq'),'".$result['id']."','".$muscleThickness."')";
                $em->getConnection()->exec($sql);
            }
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
        $output->writeln("LINES IMPORTED: " . $counter);
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
