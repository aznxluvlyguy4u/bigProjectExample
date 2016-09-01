<?php

namespace AppBundle\Command;

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
    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'animal_exterior_measurements_migration_update.csv', //TODO
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

        $cmdUtil->setStartTimeAndPrintIt();
        foreach ($csv as $line) {



            
//            $name = $line[0];
//            $measurementDate = $line[1]; //TODO format dates
//            $kind = (float) $line[2]; //Nullcheck -> empty string
//            $progress = (float) $line[3]; //Nullcheck ->  zero
//            $height = (float) $line[4]; //Nullcheck -> zero
//
//            $counter++;
//            $output->writeln($counter);
//
//            $sql = "SELECT exterior.id FROM exterior INNER JOIN measurement ON exterior.id = measurement.id INNER JOIN animal ON exterior.animal_id = animal.id WHERE animal.name = '' AND measurement_date = ''";
//            $result = $em->getConnection()->query($sql)->fetch();
//
//            if ($result['id'] != "" || $result['id'] != null) {
//
//                $sql = "UPDATE exterior SET height = '".$height."', progress = '".$progress."', kind = '".$kind."' WHERE exterior.id = ".$result['id'];
//                $em->getConnection()->exec($sql);
//            }
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
