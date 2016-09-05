<?php

namespace AppBundle\Command;

use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigrateLittersCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate litters';
    const DEFAULT_INPUT_PATH = '/home/data/JVT/projects/NSFO/Migratie/Animal/animal_litters_20160307_1349.csv';
    const BATCH_SIZE = 1000;
    const DEFAULT_MIN_VSM_ID = 1;

    const DEFAULT_START_ROW = 0;


    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'animal_litters_20160307_1349.csv',
        'ignoreFirstLine' => true
    );

    /** @var ArrayCollection $litterSets */
    private $litterSets;

    /** @var ObjectManager $em */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:litters')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        //Input folder input
        $inputFolderPath = $cmdUtil->generateQuestion('Please enter input folder path: ', self::DEFAULT_INPUT_PATH);
        $minVsmId = $cmdUtil->generateQuestion('Please enter minimum vsmId (default = 1): ', self::DEFAULT_MIN_VSM_ID);

        $this->litterSets = new ArrayCollection();

        $dataWithoutHeader = $cmdUtil::getRowsFromCsvFileWithoutHeader($inputFolderPath);

        $cmdUtil->setStartTimeAndPrintIt(count($dataWithoutHeader)*2, $minVsmId);

        $rowCount = 0;
        $this->litterSets = new ArrayCollection();
        //First save pedigree date per vsmId in an ArrayCollection
        foreach ($dataWithoutHeader as $row) {

            $this->groupLittersByEweVsmId($row);
            $rowCount++;
            $cmdUtil->setProgressBarMessage('Litters grouped into array: '.$rowCount);
        }

        $output->writeln('Removing litters of Ewes with vsmId below given minimum');
        $eweVsmIds = $this->litterSets->getKeys();
        $output->writeln('Ewes before filtering on min vsmId: '.sizeof($eweVsmIds));
        $removeVsmIds = array();
        foreach($eweVsmIds as $eweVsmId) {
            if($eweVsmId < $minVsmId) {
                $removeVsmIds[] = $eweVsmId;
            }
        }
        $eweVsmIds = array_diff($eweVsmIds, $removeVsmIds);
        $output->writeln('Ewes to process: '.sizeof($eweVsmIds));

        $output->writeln('Creating new litters...');

        $litterCount = 0;
        $eweCount = 0;
        foreach ($eweVsmIds as $eweVsmId)
        {
            /** @var Ewe $ewe */
            $ewe = $em->getRepository(Ewe::class)->findOneByName($eweVsmId);

            if($ewe != null) {
                /** @var ArrayCollection $littersDataSet */
                $littersDataSet = $this->litterSets->get($eweVsmId);
                $litterDates = $littersDataSet->getKeys();

                foreach ($litterDates as $litterDateString) {
                    $children = $littersDataSet->get($litterDateString);

                    $litterDate = new \DateTime($litterDateString);
                    $bornAliveCount = $children[0];
                    $stillbornCount = $children[1];

                    $foundLitter = $em->getRepository(Litter::class)->findOneBy([
                        'animalMother' => $ewe,
                        'litterDate' => $litterDate
                    ]);

                    if($foundLitter == null) {
//                        Litter data has not been migrated yet, so persist a new litter
                        $litter = new Litter();
                        $litter->setAnimalMother($ewe);
                        $litter->setLitterDate($litterDate);
                        $litter->setBornAliveCount($bornAliveCount);
                        $litter->setStillbornCount($stillbornCount);

                        $em->persist($litter);
                        $litterCount++;
                    }

                    $cmdUtil->advanceProgressBar(1,'Checked LitterCount: '.$litterCount.' |  EweCount: '.$eweCount.' |  last vsmId: '.$eweVsmId);
                }
                $eweCount++;

                if($eweCount%self::BATCH_SIZE == 0) {
                    DoctrineUtil::flushClearAndGarbageCollect($em);
                }
            }
        }
        DoctrineUtil::flushClearAndGarbageCollect($em);

        $output->writeln([  'Ewes processed: '.$eweCount,
                            'Litters processed: '.$litterCount
        ]);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

    private function groupLittersByEweVsmId($row)
    {

        //null checks
        if($row == null || $row == '') { return; }
        $rowParts = explode(';',$row);

        if(sizeof($rowParts) < 4) { return; }

        $vsmId = $rowParts[0];
        $litterDate = $rowParts[1];
        $bornAliveCount = $rowParts[2];
        $stillbornCount = $rowParts[3];

        /** @var ArrayCollection $eweLitters */
        $eweLitters = $this->litterSets->get($vsmId);
        if($eweLitters == null) {
            $this->litterSets->set($vsmId, new ArrayCollection());
        }

        $this->litterSets->get($vsmId)
            ->set($litterDate, [$bornAliveCount, $stillbornCount]);
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


    protected function executeOld(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        //Input folder input
        $inputFolderPath = $cmdUtil->generateQuestion('Please enter input folder path: ', self::DEFAULT_INPUT_PATH);
        $minVsmId = $cmdUtil->generateQuestion('Please enter minimum vsmId (default = 1): ', self::DEFAULT_MIN_VSM_ID);

        $this->litterSets = new ArrayCollection();

        $dataWithoutHeader = $cmdUtil::getRowsFromCsvFileWithoutHeader($inputFolderPath);

        $cmdUtil->setStartTimeAndPrintIt(count($dataWithoutHeader)*2, $minVsmId);

        $rowCount = 0;
        $this->litterSets = new ArrayCollection();
        //First save pedigree date per vsmId in an ArrayCollection
        foreach ($dataWithoutHeader as $row) {

            $this->groupLittersByEweVsmId($row);
            $rowCount++;
            $cmdUtil->setProgressBarMessage('Litters grouped into array: '.$rowCount);
        }

        $output->writeln('Removing litters of Ewes with vsmId below given minimum');
        $eweVsmIds = $this->litterSets->getKeys();
        $output->writeln('Ewes before filtering on min vsmId: '.sizeof($eweVsmIds));
        $removeVsmIds = array();
        foreach($eweVsmIds as $eweVsmId) {
            if($eweVsmId < $minVsmId) {
                $removeVsmIds[] = $eweVsmId;
            }
        }
        $eweVsmIds = array_diff($eweVsmIds, $removeVsmIds);
        $output->writeln('Ewes to process: '.sizeof($eweVsmIds));

        $output->writeln('Creating new litters...');

        $litterCount = 0;
        $eweCount = 0;
        foreach ($eweVsmIds as $eweVsmId)
        {
            /** @var Ewe $ewe */
            $ewe = $em->getRepository(Ewe::class)->findOneByName($eweVsmId);

            if($ewe != null) {
                /** @var ArrayCollection $littersDataSet */
                $littersDataSet = $this->litterSets->get($eweVsmId);
                $litterDates = $littersDataSet->getKeys();

                foreach ($litterDates as $litterDateString) {
                    $children = $littersDataSet->get($litterDateString);

                    $litterDate = new \DateTime($litterDateString);
                    $bornAliveCount = $children[0];
                    $stillbornCount = $children[1];

                    $foundLitter = $em->getRepository(Litter::class)->findOneBy([
                        'animalMother' => $ewe,
                        'litterDate' => $litterDate
                    ]);

                    if($foundLitter == null) {
//                        Litter data has not been migrated yet, so persist a new litter
                        $litter = new Litter();
                        $litter->setAnimalMother($ewe);
                        $litter->setLitterDate($litterDate);
                        $litter->setBornAliveCount($bornAliveCount);
                        $litter->setStillbornCount($stillbornCount);

                        $em->persist($litter);
                        $litterCount++;
                    }

                    $cmdUtil->advanceProgressBar(1,'Checked LitterCount: '.$litterCount.' |  EweCount: '.$eweCount.' |  last vsmId: '.$eweVsmId);
                }
                $eweCount++;

                if($eweCount%self::BATCH_SIZE == 0) {
                    DoctrineUtil::flushClearAndGarbageCollect($em);
                }
            }
        }
        DoctrineUtil::flushClearAndGarbageCollect($em);

        $output->writeln([  'Ewes processed: '.$eweCount,
            'Litters processed: '.$litterCount
        ]);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

}
