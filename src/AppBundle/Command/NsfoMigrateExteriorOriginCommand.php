<?php

namespace AppBundle\Command;

use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\EntityManager;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Util\CommandUtil;

class NsfoMigrateExteriorOriginCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrating Exterior from original data source';
    const DEFAULT_START_ROW = 0;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'animal_exterior_measurements_migration_update.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:exterior:origin')
            ->setDescription(self::TITLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csv = $this->parseCSV();
        /**
         * @var EntityManager $em
         */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);
        $counter = 0;

        $startCounter = $cmdUtil->generateQuestion('Please enter start row: ', self::DEFAULT_START_ROW);

        $cmdUtil->setStartTimeAndPrintIt();

        for($i = $startCounter; $i < sizeof($csv); $i++) {

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

                $output->write($i);

                if(NullChecker::isNotNull($measurementDate)){
                    $sql = "SELECT exterior.id as id FROM exterior  INNER JOIN measurement ON exterior.id = measurement.id INNER JOIN animal ON exterior.animal_id = animal.id WHERE animal.name = '".$name."' AND measurement.measurement_date BETWEEN '".$measurementDateStamp."' AND '".$nextDayStamp."'";
                    $result = $em->getConnection()->query($sql)->fetch();

                    if ($result) {
                        if($result['id'] != '') {
                            $output->writeln('+');
                            $sql = "UPDATE exterior SET height = '".$height."', progress = '".$progress."', kind = '".$kind."' WHERE exterior.id = '".$result['id']."'";
                            $em->getConnection()->exec($sql);
                        }
                    } else {
                        $output->writeln('');
                    }
                }
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
