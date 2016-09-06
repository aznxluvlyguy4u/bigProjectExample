<?php

namespace AppBundle\Command;

use AppBundle\Entity\Ewe;
use AppBundle\Entity\EweRepository;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
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
    const DEFAULT_MIN_EWE_ID = 1;

    const DEFAULT_START_ROW = 0;


    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'animal_litters_20160307_1349.csv',
        'ignoreFirstLine' => true
    );

    /** @var ArrayCollection $litterSets */
    private $litterSets;

    /** @var ArrayCollection */
    private $animalPrimaryKeysByVsmId;
    
    /** @var EweRepository */
    private $eweRepository;

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
        $this->eweRepository = $this->em->getRepository(Ewe::class);

        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $this->animalPrimaryKeysByVsmId = $this->eweRepository->getAnimalPrimaryKeysByVsmId();

        //Input folder input
        $inputFolderPath = $cmdUtil->generateQuestion('Please enter input folder path', self::DEFAULT_INPUT_PATH);
        $minEweId = $cmdUtil->generateQuestion('Please enter minimum ewe primaryKey (default = 1)', self::DEFAULT_MIN_EWE_ID);

        $this->litterSets = new ArrayCollection();

        $dataWithoutHeader = $cmdUtil::getRowsFromCsvFileWithoutHeader($inputFolderPath);

        $cmdUtil->setStartTimeAndPrintIt(count($dataWithoutHeader)*2, $minEweId);

        $rowCount = 0;
        $this->litterSets = new ArrayCollection();
        //First save pedigree date per eweId in an ArrayCollection
        foreach ($dataWithoutHeader as $row) {

            $this->groupLittersByPrimaryKey($row);
            $rowCount++;
            $cmdUtil->setProgressBarMessage('Litters grouped into array: '.$rowCount);
        }

        $cmdUtil->setProgressBarMessage('Removing litters of Ewes with primaryKey Id below given minimum');
        $eweIds = $this->litterSets->getKeys();
        $cmdUtil->setProgressBarMessage('Ewes before filtering on min Id: '.sizeof($eweIds));
        $removeIds = array();
        foreach($eweIds as $eweId) {
            if($eweId < $minEweId) {
                $removeIds[] = $eweId;
            }
        }
        $eweIds = array_diff($eweIds, $removeIds);
        $cmdUtil->setProgressBarMessage('Ewes to process: '.sizeof($eweIds).' | Creating new litters...');

        $litterCount = 0;
        $eweCount = 0;

        $today = new \DateTime('today');
        $todayString = $today->format('Y-m-d');

        /** @var LitterRepository $litterRepository */
        $litterRepository = $this->em->getRepository(Litter::class);
        
        foreach ($eweIds as $eweId)
        {
            if($this->isEweExists($eweId)) {
                /** @var ArrayCollection $littersDataSet */
                $littersDataSet = $this->litterSets->get($eweId);
                $litterDates = $littersDataSet->getKeys();

                foreach ($litterDates as $litterDateString) {
                    $children = $littersDataSet->get($litterDateString);

                    $litterDate = new \DateTime($litterDateString);
                    $bornAliveCount = $children[0];
                    $stillbornCount = $children[1];                    

                    if(!$this->isLitterAlreadyExists($eweId, $litterDateString)) {

                        $sql = "SELECT MAX(id) FROM litter";
                        $result = $this->em->getConnection()->query($sql)->fetch();
                        $litterId = $result['max']+1;
                        
//                      Litter data has not been migrated yet, so persist a new litter
                        $sql = "INSERT INTO litter (id, animal_mother_id, log_date, litter_date, stillborn_count, born_alive_count) VALUES ('".$litterId."','".$eweId."','".$todayString."','".$litterDateString."','".$stillbornCount."','".$bornAliveCount."')";
                        $this->em->getConnection()->exec($sql);
                        $litterCount++;
                    }

                    $cmdUtil->advanceProgressBar(1,'Checked LitterCount: '.$litterCount.' |  EweCount: '.$eweCount.' |  last Id: '.$eweId);
                }
                $eweCount++;
            }
        }

        $output->writeln([  'Ewes processed: '.$eweCount,
            'Litters processed: '.$litterCount
        ]);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

    private function groupLittersByPrimaryKey($row)
    {

        //null checks
        if($row == null || $row == '') { return; }
        $rowParts = explode(';',$row);

        if(sizeof($rowParts) < 4) { return; }

        $vsmId = $rowParts[0];
        $eweId = $this->animalPrimaryKeysByVsmId->get($vsmId);
        $litterDate = $this->reformatStringDate($rowParts[1]);
        $bornAliveCount = $rowParts[2];
        $stillbornCount = $rowParts[3];

        if(NullChecker::isNotNull($litterDate)) {

            /** @var ArrayCollection $eweLitters */
            $eweLitters = $this->litterSets->get($eweId);
            if($eweLitters == null) {
                $this->litterSets->set($eweId, new ArrayCollection());
            }

            $this->litterSets->get($eweId)
                ->set($litterDate, [$bornAliveCount, $stillbornCount]);
        }

    }


    /**
     * @param string $stringDate
     * @return string
     */
    private function reformatStringDate($stringDate)
    {
        $parts = explode('-', $stringDate);
        if(count($parts) < 2) {
            return $stringDate;
        } else {
            return $parts[2].'-'.$parts[1].'-'.$parts[0].' 00:00:00';
        }
    }


    /**
     * @param int $eweId
     * @return bool
     */
    private function isEweExists($eweId)
    {
        $sql = "SELECT id FROM ewe WHERE id = '".$eweId."'";
        $result = $this->em->getConnection()->query($sql)->fetch();
        if($result['id'] != null) {
            return true;
        } else {
            return false;
        }
    }


    private function isLitterAlreadyExists($eweId, $measurementDateString)
    {
        $sql = "SELECT id FROM litter WHERE animal_mother_id = '".$eweId."' AND litter_date = '".$measurementDateString."'";
        $result = $this->em->getConnection()->query($sql)->fetch();
        if($result['id'] != null) {
            return true;
        } else {
            return false;
        }
    }

}
