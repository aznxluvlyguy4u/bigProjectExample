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
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigrateLittersCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate litters';
    const OLD_INPUT_PATH = '/home/data/JVT/projects/NSFO/Migratie/Animal/animal_litters_20160307_1349.csv';
    const DEFAULT_INPUT_PATH = '/home/data/JVT/projects/NSFO/Migratie/Animal/animal_litters_20161007_1156.csv';
    const BATCH_SIZE = 1000;
    const DEFAULT_MIN_EWE_ID = 1;
    const DEFAULT_OPTION = 0;

    const DEFAULT_START_ROW = 0;
    const SEPARATOR = '__';


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

    /** @var array */
    private $dataWithoutHeader;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var ObjectManager $em */
    private $em;

    /** @var Connection */
    private $conn;

    /** @var array */
    private $litterSearchArray;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:litters')
            ->setDescription(self::TITLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->eweRepository = $this->em->getRepository(Ewe::class);
        $this->output = $output;

        $this->conn = $em->getConnection();

        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate Litters from source file (incl mother, excl children)', "\n",
            '2: Match children with existing litters and set father in litter', "\n",
            '3: Find missing father in animal by searching in litter', "\n",
            '4: Generate all litter group ids (uln_orderedCount)', "\n",
            '5: Check if children in litter matches bornAliveCount value', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $this->migrateLitters();
                break;

            case 2:
                $this->matchChildrenWithExistingLittersAndSetFatherInLitter();
                break;

            case 3:
                $this->findMissingFathersForAnimalFromLitter();
                break;

            case 4:
                $this->setLitterGroupIds();
                break;

            case 5:
                $this->checkBornAliveCount();
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }

    }
    
    
    private function checkBornAliveCount()
    {
        $outputFolder = $this->getContainer()->get('kernel')->getRootDir().'/Resources/outputs';
        NullChecker::createFolderPathIfNull($outputFolder);

        $notEnoughChildren = $outputFolder.'/litter_count_check__children_missing.csv';
        $noChildren = $outputFolder.'/litter_count_check__no_children.csv';
        $tooMuchChildren = $outputFolder.'/litter_count_check__too_much_children.csv';

        $errorMessageHeaders = 'litterId;ulnMoeder;worpDatum;kinderenInDbVanWorp;worpGrootteWaardeImport';

        file_put_contents($noChildren, $errorMessageHeaders. "\n", FILE_APPEND);
        file_put_contents($tooMuchChildren, $errorMessageHeaders . "\n", FILE_APPEND);
        file_put_contents($notEnoughChildren, $errorMessageHeaders . "\n", FILE_APPEND);



        $minId = $this->cmdUtil->generateQuestion('Get litters starting from given primary key/id, default is '.self::DEFAULT_MIN_EWE_ID,self::DEFAULT_MIN_EWE_ID);
        /** @var LitterRepository $litterRepository */
        $litterRepository = $this->em->getRepository(Litter::class);
        $maxId = $litterRepository->getMaxLitterId();

        $sql = "SELECT COUNT(id) FROM litter WHERE id >= '".$minId."'";
        $totalLitterSize = $this->conn->query($sql)->fetch()['count'];

        $this->cmdUtil->setStartTimeAndPrintIt($totalLitterSize, 1, 'Data retrieved from database. Now checking born alive count...');

        $litterCount = 0;
        $missMatchedLitterCounts = 0;

        for($i = $minId; $i <= $maxId; $i += self::BATCH_SIZE) {

            $litters = $litterRepository->getLittersById($i, $i+self::BATCH_SIZE-1);
            foreach ($litters as $litter) {
                /** @var Litter $litter */
                $childrenBornAliveCount = $litterRepository->getChildrenByAliveState($litter->getId(), true);
                $childrenCountValue = $litter->getBornAliveCount();

                if($childrenBornAliveCount != $childrenCountValue) {
                    $missMatchedLitterCounts++;

                    $uln = $litter->getAnimalMother()->getUln();
                    $litterDate = $litter->getLitterDate()->format('Y-m-d');

                    $errorMessage = ''.$litter->getId().';'.$uln.';'.$litterDate.';'.$childrenBornAliveCount.';'.$childrenCountValue;

                    if($childrenBornAliveCount == 0) {
                        file_put_contents($noChildren, $errorMessage. "\n", FILE_APPEND);
                    } elseif ($childrenBornAliveCount > $childrenCountValue) {
                        file_put_contents($tooMuchChildren, $errorMessage . "\n", FILE_APPEND);
                    } elseif ($childrenBornAliveCount < $childrenCountValue) {
                        file_put_contents($notEnoughChildren, $errorMessage . "\n", FILE_APPEND);
                    }

                }

                $litterCount++;

                $message = 'Checked litters: '.$litterCount.'/'.$totalLitterSize.' |Litters: '.$litterCount.' |Errors: '.$missMatchedLitterCounts;
                $this->cmdUtil->advanceProgressBar(1, $message);
            }
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

    }


    private function setLitterGroupIds()
    {
        $minId = $this->cmdUtil->generateQuestion('Get ewes starting from given primary key/id, default is '.self::DEFAULT_MIN_EWE_ID,self::DEFAULT_MIN_EWE_ID);
        $maxId = $this->eweRepository->getMaxEweId();
        $batchSize = self::BATCH_SIZE;

        $sql = "SELECT COUNT(id) FROM ewe WHERE id >= '".$minId."'";
        $totalEweCount = $this->conn->query($sql)->fetch()['count'];

        $this->cmdUtil->setStartTimeAndPrintIt($totalEweCount, 1, 'Data retrieved from database. Now finding fathers for children...');
        
        $eweCount = 0;
        $litterCount = 0;

        $eweId = 0;
        $lastFlushedEweId = 0;
        for($i = $minId; $i <= $maxId; $i += self::BATCH_SIZE) {


            $ewes = $this->eweRepository->getEwesById($i, $i+self::BATCH_SIZE-1);
            foreach ($ewes as $ewe) {
                /** @var Ewe $ewe */
                $this->eweRepository->generateLitterIds($ewe, true, false);

                $litterCount += $ewe->getLitters()->count();
                $eweCount++;
                $eweId = $ewe->getId();

                $message = 'Ewes: '.$eweCount.'/'.$totalEweCount.' | Litters: '.$litterCount.' Last flushed EweId: '.$lastFlushedEweId;
                $this->cmdUtil->advanceProgressBar(1, $message);
            }
            $lastFlushedEweId = $eweId;
            DoctrineUtil::flushClearAndGarbageCollect($this->em);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function findMissingFathersForAnimalFromLitter()
    {
        //Retrieve data from db
        $totalMissingFathersStart = $this->getMissingFathersCount();

        $sql = "SELECT id as animal_id, animal.litter_id as animal_litter_id FROM animal WHERE animal.litter_id IS NOT NULL AND animal.parent_father_id IS NULL";
        $childrenResults = $this->conn->query($sql)->fetchAll();

        $sql = "SELECT animal_father_id, id as litter_id FROM litter WHERE animal_father_id IS NOT NULL";
        $litterResults = $this->conn->query($sql)->fetchAll();

        $this->cmdUtil->setStartTimeAndPrintIt(count($childrenResults), 1, 'Data retrieved from database. Now finding fathers for children...');

        //Create search arrays
        $animalSearchArray = new ArrayCollection();
        $fatherSearchArray = new ArrayCollection();

        foreach ($childrenResults as $childResult) {
            $animalSearchArray->set($childResult['animal_litter_id'], $childResult['animal_id']);
        }
        foreach ($litterResults as $litterResult) {
            $fatherSearchArray->set($litterResult['litter_id'], $litterResult['animal_father_id']);
        }

        $missingFathersCount = 0;
        $foundFathersCount = 0;
        $animalLitterIds = $animalSearchArray->getKeys();
        foreach ($animalLitterIds as $litterId) {
            $animalId = $animalSearchArray->get($litterId);
            $fatherId = $fatherSearchArray->get($litterId);
            if ($litterId != null && $fatherId != null) {
                $sql = "UPDATE animal SET parent_father_id = '" . $fatherId . "' WHERE id = '" . $animalId . "'";
                $this->conn->exec($sql);

                $animalProgressBarMessage = 'FATHER FOUND FOR ANIMAL: ' . $animalId;
            } else {
                $animalProgressBarMessage = 'NO FATHER FOUND FOR ANIMAL: ' . $animalId;
            }


            $this->cmdUtil->advanceProgressBar(1, $animalProgressBarMessage .
                '  | FATHERS FOUND: ' . $foundFathersCount .
                '  | FATHERS MISSING: ' . $missingFathersCount);
        }
        $totalMissingFathersEnd = $this->getMissingFathersCount();
        $this->cmdUtil->setProgressBarMessage('Total fathers missing, before: ' . $totalMissingFathersStart . ' | after: ' . $totalMissingFathersEnd);
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function matchChildrenWithExistingLittersAndSetFatherInLitter()
    {
        $sql = "SELECT DATE(date_of_birth) as date_of_birth, id as animal_id, animal.litter_id as animal_litter_id, parent_mother_id, parent_father_id FROM animal";
        $childrenResults = $this->conn->query($sql)->fetchAll();

        $sql = "SELECT animal_mother_id, DATE(litter.litter_date) as litter_date, id as litter_id FROM litter";
        $litterResults = $this->conn->query($sql)->fetchAll();

        $this->cmdUtil->setStartTimeAndPrintIt(count($childrenResults), 1, 'Data retrieved from database. Now matching Children with Litters...');

        //Create search arrays
        $animalSearchArray = new ArrayCollection();
        $litterSearchArray = new ArrayCollection();

        foreach ($childrenResults as $childResult) {
            $motherDateString = $this->createAnimalSearchString($childResult);
            $childrenArray = $animalSearchArray->get($motherDateString);
            if($childrenArray == null) {
                $childrenArray = new ArrayCollection();
            }
            $childrenArray->add($childResult);
            $animalSearchArray->set($motherDateString, $childrenArray);
        }

        foreach ($litterResults as $litterResult) {
            $motherDateString = $this->createLitterSearchString($litterResult);
            $litterSearchArray->set($motherDateString, $litterResult['litter_id']);
        }

        //Match arrays: For each child find a matching litter
        $foundLittersCount = 0;
        $noLitterFoundCount = 0;
        $missingFatherCount = 0;
        $animalMotherDateStrings = $animalSearchArray->getKeys();
        foreach ($animalMotherDateStrings as $animalMotherDateString) {
            $litterId = $litterSearchArray->get($animalMotherDateString);
            $childrenArray = $animalSearchArray->get($animalMotherDateString);

            foreach($childrenArray as $childResult) {
                $animalId = $childResult['animal_id'];
                $fatherId = $childResult['parent_father_id'];

                if ($litterId != null) {

                    $sql = "UPDATE animal SET litter_id = '" . $litterId . "' WHERE id = '" . $animalId . "'";
                    $this->conn->exec($sql);

                    if ($fatherId != null) {
                        $sql = "UPDATE litter SET animal_father_id = '" . $fatherId . "' WHERE id = '" . $litterId . "'";
                        $this->conn->exec($sql);
                        $missingFatherCount++;
                    }

                    $foundLittersCount++;
                    $animalProgressBarMessage = 'LITTER FOUND FOR ANIMAL (id): '.$animalId.' | Found litters: '.$foundLittersCount.' | Missing litters: '.$noLitterFoundCount;
                } else {
                    $noLitterFoundCount++;
                    $animalProgressBarMessage = 'MISSING LITTER FOR ANIMAL (id): '.$animalId.' | Found litters: '.$foundLittersCount.' | Missing litters: '.$noLitterFoundCount;
                }
                $this->cmdUtil->advanceProgressBar(1, $animalProgressBarMessage);
            }
        }
        $animalProgressBarMessage = $foundLittersCount.'  | LITTERS MISSING: '.$noLitterFoundCount.'  | FATHERS MISSING: '.$missingFatherCount;
        $this->cmdUtil->setProgressBarMessage($animalProgressBarMessage);
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param array $childResult
     * @return string
     */
    private function createAnimalSearchString($childResult)
    {
        $dateTime = substr($childResult['date_of_birth'], 0, 10);
        $motherId = $childResult['parent_mother_id'];

        return $motherId . self::SEPARATOR . $dateTime;
    }


    /**
     * @param array $litterResult
     * @return string
     */
    private function createLitterSearchString($litterResult)
    {
        $dateTime = substr($litterResult['litter_date'], 0, 10);
        $motherId = $litterResult['animal_mother_id'];

        return $motherId . self::SEPARATOR . $dateTime;
    }


    private function migrateLitters()
    {
        //Input folder input
        $inputFolderPath = $this->cmdUtil->generateQuestion('Please enter input folder path (empty for default path)', self::DEFAULT_INPUT_PATH);
        $this->output->writeln('Chosen path: '.$inputFolderPath);
        $this->dataWithoutHeader = CommandUtil::getRowsFromCsvFileWithoutHeader($inputFolderPath);

        $minEweId = $this->cmdUtil->generateQuestion('Please enter minimum ewe primaryKey (default = 1)', self::DEFAULT_MIN_EWE_ID);

        $this->cmdUtil->setStartTimeAndPrintIt(count($this->dataWithoutHeader) * 2, $minEweId);

        //Create SearchArrays
        $this->animalPrimaryKeysByVsmId = $this->eweRepository->getAnimalPrimaryKeysByVsmId();

        $this->litterSearchArray = [];
        $sql = "SELECT CONCAT(animal_mother_id,'".self::SEPARATOR."',litter_date) as key FROM litter";
        $results = $this->conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            $key = $result['key'];
            $this->litterSearchArray[$key] = $key;
        }


        $rowCount = 0;
        $this->litterSets = new ArrayCollection();
        //First save pedigree date per eweId in an ArrayCollection
        foreach ($this->dataWithoutHeader as $row) {

            $this->groupLittersByPrimaryKey($row);
            $rowCount++;
            $this->cmdUtil->setProgressBarMessage('Litters grouped into array: ' . $rowCount);
        }


        $this->cmdUtil->setProgressBarMessage('Removing litters of Ewes with primaryKey Id below given minimum');
        $eweIds = $this->litterSets->getKeys();
        $this->cmdUtil->setProgressBarMessage('Ewes before filtering on min Id: ' . sizeof($eweIds));
        $removeIds = array();
        foreach ($eweIds as $eweId) {
            if ($eweId < $minEweId) {
                $removeIds[] = $eweId;
            }
        }
        $eweIds = array_diff($eweIds, $removeIds);
        sort($eweIds);
        $this->cmdUtil->setProgressBarMessage('Ewes to process: ' . sizeof($eweIds) . ' | Creating new litters...');

        $litterCount = 0;
        $eweCount = 0;
        $skippedCount = 0;

        $today = new \DateTime('today');
        $todayString = $today->format('Y-m-d');

        //Check for broken half imports and delete them
        $this->deleteBrokenLitterImport();

        foreach ($eweIds as $eweId) {
            if ($this->isEweExists($eweId)) {
                /** @var ArrayCollection $littersDataSet */
                $littersDataSet = $this->litterSets->get($eweId);
                $litterDates = $littersDataSet->getKeys();

                foreach ($litterDates as $litterDateString) {
                    $children = $littersDataSet->get($litterDateString);

                    $litterDate = new \DateTime($litterDateString);
                    $bornAliveCount = $children[0];
                    $stillbornCount = $children[1];

                    //CREATE LITTERS
                    if (!$this->isLitterAlreadyExists($eweId, $litterDateString)) {

//                      Litter data has not been migrated yet, so persist a new litter
                        $sql = "INSERT INTO declare_nsfo_base (id, log_date, request_state, type
                                ) VALUES (nextval('declare_nsfo_base_id_seq'),'" .$todayString."','IMPORTED','Litter')
                                RETURNING id";
                        $id = $this->conn->query($sql)->fetch()['id'];

                        $sql = "INSERT INTO litter (id, animal_mother_id, litter_date, stillborn_count, born_alive_count,
                                status
                                ) VALUES ($id,'" .$eweId."','".$litterDateString."','".$stillbornCount."','".$bornAliveCount."','INCOMPLETE')";
                        $this->conn->exec($sql);

                        $litterCount++;
                    } else {
                        $skippedCount++;
                    }

                    $this->cmdUtil->advanceProgressBar(1, 'LitterCount inserted|skipped: '.$litterCount.'|'.$skippedCount.' -  EweCount: ' . $eweCount . ' |  last Id: ' . $eweId);
                }
                $eweCount++;
            }
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function groupLittersByPrimaryKey($row)
    {

        //null checks
        if ($row == null || $row == '') {
            return;
        }
        $rowParts = explode(';', $row);

        if (sizeof($rowParts) < 4) {
            return;
        }

        $vsmId = $rowParts[0];
        $eweId = $this->animalPrimaryKeysByVsmId->get($vsmId);
        $litterDate = $this->reformatStringDate($rowParts[1]);
        $bornAliveCount = $rowParts[2];
        $stillbornCount = $rowParts[3];

        if (NullChecker::isNotNull($litterDate)) {

            /** @var ArrayCollection $eweLitters */
            $eweLitters = $this->litterSets->get($eweId);
            if ($eweLitters == null) {
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
        if (count($parts) < 2) {
            return $stringDate;
        } else {
            return $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT) . ' 00:00:00';
        }
    }


    /**
     * @param int $eweId
     * @return bool
     */
    private function isEweExists($eweId)
    {
        $sql = "SELECT id FROM ewe WHERE id = '" . $eweId . "'";
        $result = $this->conn->query($sql)->fetch();
        if ($result['id'] != null) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $eweId
     * @param $measurementDateString
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function isLitterAlreadyExists($eweId, $measurementDateString)
    {
        $searchKey = $eweId.self::SEPARATOR.$measurementDateString;
        return array_key_exists($searchKey, $this->litterSearchArray);
    }


    /**
     * @return int
     */
    private function getMissingFathersCount()
    {
        $sql = "SELECT COUNT(id) FROM animal WHERE animal.parent_father_id IS NULL";
        return $this->conn->query($sql)->fetch()['count'];
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteBrokenLitterImport()
    {
        $sql = "SELECT COUNT(*) FROM declare_nsfo_base a
                    LEFT JOIN litter ON a.id = litter.id
                  WHERE litter.id ISNULL AND type = 'Litter'";
        $count = $this->conn->query($sql)->fetch()['count'];

        if($count > 0) {
            $sql = "DELETE FROM declare_nsfo_base
                WHERE id IN(
                  SELECT a.id FROM declare_nsfo_base a
                    LEFT JOIN litter ON a.id = litter.id
                  WHERE litter.id ISNULL AND type = 'Litter'
                )";
            $this->conn->exec($sql);
            $this->output->writeln($count.' broken litter imports deleted');
        } else {
            $this->output->writeln('There are no broken litter imports!');
        }

    }
}