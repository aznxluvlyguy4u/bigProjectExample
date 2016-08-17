<?php

namespace AppBundle\Command;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Util\CommandUtil;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigrateParentsCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrating Animal Parents';
    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'animal_parents_migration.csv',
        'ignoreFirstLine' => false
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:parents')
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
        $doctrine = $this->getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Constant::ANIMAL_REPOSITORY);
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);
        $counter = 0;

        $cmdUtil->setStartTimeAndPrintIt();

        foreach ($csv as $line){

            $counter++;

            if($counter % 10000 == 0){
                $output->writeln($counter);
            }

            if($line[4] == 'MALE' AND $line[4] != '') {
                /**
                 * @var Animal $animal
                 */
                $sql = "SELECT animal.id, animal.gender, ram.object_type FROM animal LEFT JOIN ram ON animal.id = ram.id WHERE uln_country_code = '".$line[2]."' AND uln_number = '".$line[3]."' ORDER BY animal.id DESC LIMIT 1";
                $result = $em->getConnection()->query($sql)->fetch();

                if ($result['object_type'] == "" || $result['object_type'] == null) {
                    $sql = "INSERT INTO ram VALUES (".$result['id'].", 'Ram')";
                    $em->getConnection()->exec($sql);

                    $sql = "DELETE FROM neuter WHERE id = ".$result['id'];
                    $em->getConnection()->exec($sql);
                }

                $sql = "UPDATE animal SET parent_father_id = (SELECT MAX(id) FROM animal WHERE uln_country_code = '".$line[2]."' AND uln_number = '".$line[3]."') WHERE uln_country_code = '".$line[0]."' AND uln_number = '".$line[1]."'";
                $em->getConnection()->exec($sql);

            }

            if($line[4] == 'FEMALE' AND $line[4] != '') {
                /**
                 * @var Animal $animal
                 */
                $sql = "SELECT animal.id, animal.gender, ewe.object_type FROM animal LEFT JOIN ewe ON animal.id = ewe.id WHERE uln_country_code = '".$line[2]."' AND uln_number = '".$line[3]."' ORDER BY animal.id DESC LIMIT 1";
                $result = $em->getConnection()->query($sql)->fetch();

                if ($result['object_type'] == "" || $result['object_type'] == null) {
                    $sql = "INSERT INTO ewe VALUES (".$result['id'].", 'Ewe')";
                    $em->getConnection()->exec($sql);

                    $sql = "DELETE FROM neuter WHERE id = ".$result['id'];
                    $em->getConnection()->exec($sql);
                }

                $sql = "UPDATE animal SET parent_mother_id = (SELECT MAX(id) FROM animal WHERE uln_country_code = '".$line[2]."' AND uln_number = '".$line[3]."') WHERE uln_country_code = '".$line[0]."' AND uln_number = '".$line[1]."'";
                $em->getConnection()->exec($sql);
            }
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
        $output->writeln("LINES IMPORTED: " . $counter);
        $output->writeln("TOTAL LINES: " . sizeof($csv));
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
            while (($data = fgetcsv($handle, 100, ",")) !== FALSE) {
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
