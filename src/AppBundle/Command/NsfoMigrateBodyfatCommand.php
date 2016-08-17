<?php

namespace AppBundle\Command;

use AppBundle\Constant\Constant;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\Fat1;
use AppBundle\Entity\Fat2;
use AppBundle\Entity\Fat3;
use AppBundle\Entity\Inspector;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigrateBodyfatCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrating Animal Body Fat Measurements';
    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'fat_measurements_migration.csv',
        'ignoreFirstLine' => false
    );


    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:bodyfat')
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
        $repo_inspector = $doctrine->getRepository(Inspector::class);
        $counter = 0;

        foreach ($csv as $line) {
            $animal = $repository->findOneBy(
                array('ulnCountryCode' => $line[0], 'ulnNumber' => $line[1])
            );

            if($animal) {
                $bodyFat = new BodyFat();
                $bodyFat->setAnimal($animal);
                $bodyFat->setLogDate(new \DateTime($line[2]));
                $bodyFat->setMeasurementDate(new \DateTime($line[3]));

                if($line[4] =! "") {
                    $inspector = $repo_inspector->findOneById($line[4]);
                    $bodyFat->setInspector($inspector);
                }

                if($line[6] =! "") {
                    /**
                     * @var Fat1 $fat1
                     */
                    $fat1 = new Fat1();
                    $fat1->setFat($line[6]);
                    $fat1->setMeasurementDate(new \DateTime($line[3]));
                    $bodyFat->setFat1($fat1);
                    $em->persist($fat1);
                }

                if($line[7] =! "") {
                    /**
                     * @var Fat2 $fat2
                     */
                    $fat2 = new Fat2();
                    $fat2->setFat($line[7]);

                    $fat2->setMeasurementDate(new \DateTime($line[3]));
                    $bodyFat->setFat2($fat2);
                    $em->persist($fat2);
                }

                if($line[8] =! "") {
                    /**
                     * @var Fat3 $fat3
                     */
                    $fat3 = new Fat3();
                    $fat3->setFat($line[8]);

                    $fat3->setMeasurementDate(new \DateTime($line[3]));
                    $bodyFat->setFat3($fat3);
                    $em->persist($fat3);
                }

                $em->persist($bodyFat);
                $counter++;

                if($counter % 1000 == 0) {
                    $em->flush();
                    $em->clear();
                    gc_collect_cycles();

                }
                $output->writeln($counter);
            }

        }
        $em->flush();
        $em->clear();
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
