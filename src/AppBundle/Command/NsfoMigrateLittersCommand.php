<?php

namespace AppBundle\Command;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\EweRepository;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\TimeUtil;
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
    const OUTPUT_FOLDER_NAME = '/Resources/outputs/migration/';
    const OUTPUT_FILE_NAME = 'updated_litters.csv';
    const OUTPUT_FILE_NAME_LITTER_DATES = 'strange_litter_dates.csv';
    const OUTPUT_FILE_NAME_HALF_YEAR_LITTER = 'half_year_litters.csv';
    const BATCH_SIZE = 1000;
    const DEFAULT_MIN_EWE_ID = 1;
    const DEFAULT_OPTION = 0;

    const DEFAULT_START_ROW = 0;
    const SEPARATOR = '__';


    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/migration/',
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

    /** @var array */
    private $litterValuesSearchArray;

    /** @var array */
    private $littersWithDateWithin4MonthsOfOtherLittersInDatabase;

    /** @var string */
    private $rootDir;

    /** @var string */
    private $outputFolder;

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
        $this->rootDir = $this->getContainer()->get('kernel')->getRootDir();
        //Initialize outputFolder if null
        NullChecker::createFolderPathIfNull($this->rootDir.self::OUTPUT_FOLDER_NAME);
        $this->outputFolder = $this->rootDir.self::OUTPUT_FOLDER_NAME;

        $this->conn = $em->getConnection();

        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate Litters from source file (incl mother, excl children) and update production cache of mother', "\n",
            '2: Match children with existing litters and set father in litter', "\n",
            '3: Find missing father in animal by searching in litter', "\n",
            '4: Generate all litter group ids (uln_orderedCount)', "\n",
            '5: Check if children in litter matches bornAliveCount value', "\n",
            '6: Update mismatched n-ling data in cache', "\n",
            '7: Printout strange litter dates', "\n",
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

            case 6:
                AnimalCacher::updateAllMismatchedNLingData($em, $this->cmdUtil, $this->output);
                AnimalCacher::updateNonZeroNLingInCacheWithoutLitter($em, $this->cmdUtil, $this->output);
                break;

            case 7:
                $this->printOutStrangeLitterDates();
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

        $this->littersWithDateWithin4MonthsOfOtherLittersInDatabase = $this->getLittersWithDateWithin4MonthsOfOtherLittersInDatabase();

        $this->litterSearchArray = [];
        $this->litterValuesSearchArray = [];
        $sql = "SELECT litter_date, animal_mother_id, name,
                    l.id, litter_group, born_alive_count, stillborn_count,
                    CONCAT(uln_country_code, uln_number) as uln, CONCAT(pedigree_country_code, pedigree_number) as stn
                FROM litter l INNER JOIN animal a ON l.animal_mother_id = a.id";
        $results = $this->conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            $eweId = $result['animal_mother_id'];
            $litterDate = $result['litter_date'];
            $key = $eweId.self::SEPARATOR.$litterDate;
            $this->litterSearchArray[$key] = $key;
            $values = [
                'id' => intval($result['id']),
                'litter_group' => $result['litter_group'],
                'born_alive_count' => intval($result['born_alive_count']),
                'stillborn_count' => intval($result['stillborn_count']),
                'uln' => $result['uln'],
                'stn' => $result['stn'],
                'ewe_id' => $eweId,
                'litter_date' => $litterDate,
                'vsm_id' => $result['name'],
            ];
            $this->litterValuesSearchArray[$key] = $values;
        }

        //Write headers for errors file
        $this->writeMigrationErrorRowToCsv('worp_id;uln;stn;ooi_id;vsm_id;worpdatum;levendgeboren;levendgeboren_oud;doodgeboren;doodgeboren_old;');

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
        $updatedCount = 0;
        $incorrectLitterDateCount = 0;
        $productionCacheUpdatedCount = 0;

        $today = new \DateTime('today');
        $todayString = $today->format('Y-m-d');

        //Check for broken half imports and delete them
        $this->deleteBrokenLitterImport();

        foreach ($eweIds as $eweId) {
            if ($this->isEweExists($eweId)) {
                /** @var ArrayCollection $littersDataSet */
                $littersDataSet = $this->litterSets->get($eweId);
                $litterDates = $littersDataSet->getKeys();

                $areAnyLittersUpdated = false;

                foreach ($litterDates as $litterDateString) {
                    $children = $littersDataSet->get($litterDateString);

                    $litterDate = new \DateTime($litterDateString);
                    $bornAliveCount = intval($children[0]);
                    $stillbornCount = intval($children[1]);


                    if($this->isLitterDateWithin4MonthsOfOtherLittersInDatabase($eweId, $litterDateString)) {
                        $incorrectLitterDateCount++;

                    } elseif (!$this->isLitterAlreadyExists($eweId, $litterDateString)) {
                        //CREATE LITTERS

                        //Litter data has not been migrated yet, so persist a new litter
                        $sql = "INSERT INTO declare_nsfo_base (id, log_date, request_state, type
                                ) VALUES (nextval('declare_nsfo_base_id_seq'),'" .$todayString."','IMPORTED','Litter')
                                RETURNING id";
                        $id = $this->conn->query($sql)->fetch()['id'];

                        $sql = "INSERT INTO litter (id, animal_mother_id, litter_date, stillborn_count, born_alive_count,
                                status
                                ) VALUES ($id,'" .$eweId."','".$litterDateString."','".$stillbornCount."','".$bornAliveCount."','INCOMPLETE')";
                        $this->conn->exec($sql);

                        $litterCount++;
                        $areAnyLittersUpdated = true;
                    } else {
                        //If it already exists, check if values are equal. Otherwise update
                        $values = $this->litterValuesSearchArray[$eweId.self::SEPARATOR.$litterDateString];
                        $bornAliveCountInDb = $values['born_alive_count'];
                        $stillbornCountInDb = $values['stillborn_count'];

                        $valuesAreIdentical = $bornAliveCount == $bornAliveCountInDb && $stillbornCountInDb == $stillbornCountInDb;

                        if($valuesAreIdentical) {
                            $skippedCount++;

                        } else {
                            //Fix values
                            $id = $values['id'];
                            $litterGroupInDb = $values['litter_group'];
                            $uln = $values['uln'];
                            $stn = $values['stn'];
                            $eweId = $values['ewe_id'];
                            $vsmId = $values['vsm_id'];

                            $row = $id.';'.$uln.';'.$stn.';'.$eweId.';'.$vsmId.';'.$litterDateString.';'.$bornAliveCount.';'.$bornAliveCountInDb.';'.$stillbornCount.';'.$stillbornCountInDb.';';
                            $this->writeMigrationErrorRowToCsv($row);
                            
                            $sql = "UPDATE litter SET born_alive_count = ".$bornAliveCount.", stillborn_count = ".$stillbornCount."
                                    WHERE id = ".$id;
                            $this->conn->exec($sql);

                            $sql = "UPDATE declare_nsfo_base SET log_date = '".$todayString."' WHERE id = ".$id;
                            $this->conn->exec($sql);

                            $updatedCount++;
                            $areAnyLittersUpdated = true;
                        }
                    }

                    if($areAnyLittersUpdated) {
                        $isCacheUpdated = AnimalCacher::updateProductionString($this->em, $eweId);
                        if($isCacheUpdated) { $productionCacheUpdatedCount++; }
                    }

                    $this->cmdUtil->advanceProgressBar(1, 'LitterCount inserted|updated|skipped|wrong: '.$litterCount.'|'.$updatedCount.'|'.$skippedCount.'|'.$incorrectLitterDateCount.' - Ewe Count|lastId: '.$eweCount.'|'.$eweId.' - ProductionCacheUpdated: '.$productionCacheUpdatedCount);
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


    /**
     * @param $row
     */
    private function writeMigrationErrorRowToCsv($row)
    {
        file_put_contents($this->outputFolder.self::OUTPUT_FILE_NAME, $row."\n", FILE_APPEND);
    }


    private function printOutStrangeLitterDates()
    {
        $sql = "SELECT a.name as vsm_id, r.animal_mother_id, l.ubn,
                  CONCAT(uln_country_code, uln_number) as uln, CONCAT(pedigree_country_code, pedigree_number) as stn,
                  litter_date, litter_group, born_alive_count, stillborn_count, ped.abbreviation FROM litter r
                INNER JOIN (
                    SELECT l.animal_mother_id FROM litter l
                      INNER JOIN (
                                   SELECT animal_mother_id FROM litter a
                                   WHERE litter_group NOTNULL
                                 )y ON y.animal_mother_id = l.animal_mother_id
                      INNER JOIN (
                                   SELECT animal_mother_id FROM litter b
                                   WHERE litter_group ISNULL
                                 )x ON x.animal_mother_id = l.animal_mother_id
                    GROUP BY l.animal_mother_id
                    )z ON z.animal_mother_id = r.animal_mother_id
                INNER JOIN animal a ON a.id = r.animal_mother_id
                LEFT JOIN pedigree_register ped ON a.pedigree_register_id = ped.id
                LEFT JOIN location l ON l.id = a.location_id
                ORDER BY r.animal_mother_id, r.litter_date";
        $results = $this->conn->query($sql)->fetchAll();

        $totalCount = count($results);
        if($totalCount == 0) { $this->output->writeln('No strange litterDates!'); return; }

        $this->cmdUtil->setStartTimeAndPrintIt($totalCount,1, 'creating searchArrays');

        $headerRow = 'animalId;uln;stn;stamboek;maandVerschilWorpDatumOudEnNieuw;worpdatumNieuwwCsv;worpdatumOudCsv;levendNieuw;levendOud;doodgebNew;doodgebOud;';
        file_put_contents($this->outputFolder.self::OUTPUT_FILE_NAME_LITTER_DATES, $headerRow."\n", FILE_APPEND);
        file_put_contents($this->outputFolder.self::OUTPUT_FILE_NAME_HALF_YEAR_LITTER, $headerRow."\n", FILE_APPEND);

        //Group by ewe
        $groupedSearchArray = [];
        foreach ($results as $result) {
            $eweId = $result['animal_mother_id'];

            $group = [];
            if(array_key_exists($eweId, $groupedSearchArray)) {
                $group = $groupedSearchArray[$eweId];
            }

            $group[] = $result;
            $groupedSearchArray[$eweId] = $group;
        }

        $eweIds = array_keys($groupedSearchArray);
        sort($eweIds);

        $ewesCount = 0;
        $strangeLitterDateCount = 0;
        $halfYearLitterCount = 0;
        foreach ($eweIds as $eweId) {
            $group = $groupedSearchArray[$eweId];

            $oldLitterDatesAndResults = [];
            $newLitterDatesAndResults = [];

            foreach ($group as $result) {
                $litterDate = $result['litter_date'];
                $litterGroup = $result['litter_group'];
                if($litterGroup == null) {
                    $newLitterDatesAndResults[$litterDate] = $result;
                } else {
                    $oldLitterDatesAndResults[$litterDate] = $result;
                }
            }

            $oldLitterDates = array_keys($oldLitterDatesAndResults);
            $newLitterDates = array_keys($newLitterDatesAndResults);
            foreach ($newLitterDates as $newLitterDateString) {
                foreach ($oldLitterDates as $oldLitterDateString) {
                    $newLitterDate = new \DateTime($newLitterDateString);
                    $oldLitterDate = new \DateTime($oldLitterDateString);
                    $ageInMonths = TimeUtil::getAgeMonths($newLitterDate, $oldLitterDate);
                    
                    if($ageInMonths < 6) {
                        $newResult = $newLitterDatesAndResults[$newLitterDateString];
                        $oldResult = $oldLitterDatesAndResults[$oldLitterDateString];
                        $row = $this->parseStrangeLitterDateRow($eweId, $newResult, $oldResult, $newLitterDateString, $oldLitterDateString, $ageInMonths);
                        file_put_contents($this->outputFolder.self::OUTPUT_FILE_NAME_LITTER_DATES, $row."\n", FILE_APPEND);
                        $strangeLitterDateCount++;

                    } elseif($ageInMonths < 8) {
                        $newResult = $newLitterDatesAndResults[$newLitterDateString];
                        $oldResult = $oldLitterDatesAndResults[$oldLitterDateString];
                        $row = $this->parseStrangeLitterDateRow($eweId, $newResult, $oldResult, $newLitterDateString, $oldLitterDateString, $ageInMonths);
                        file_put_contents($this->outputFolder.self::OUTPUT_FILE_NAME_HALF_YEAR_LITTER, $row."\n", FILE_APPEND);
                        $halfYearLitterCount++;
                    }
                }
            }
            $ewesCount++;
            $this->cmdUtil->advanceProgressBar(1, 'StrangeLitterDateCount|halfYearLitterCount: '.$strangeLitterDateCount.'|'.$halfYearLitterCount);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }

    private function parseStrangeLitterDateRow($eweId, $newResult, $oldResult, $newLitterDate, $oldLitterDate, $ageInMonths)
    {
        $uln = $newResult['uln'];
        $stn = $newResult['stn'];
        $abbreviation = $newResult['abbreviation'];
        $bornAliveCountNew = $newResult['born_alive_count'];
        $stillbornCountNew = $newResult['stillborn_count'];
        $bornAliveCountOld = $oldResult['born_alive_count'];
        $stillbornCountOld = $oldResult['stillborn_count'];
        $newLitterDate = rtrim($newLitterDate, ' 00:00:00');
        $oldLitterDate = rtrim($oldLitterDate, ' 00:00:00');

        return $eweId.';'.$uln.';'.$stn.';'.$abbreviation.';'.$ageInMonths.';'.$newLitterDate.';'.$oldLitterDate.';'.$bornAliveCountNew.';'.$bornAliveCountOld.';'.$stillbornCountNew.';'.$stillbornCountOld.';';
    }


    private function isLitterDateWithin4MonthsOfOtherLittersInDatabase($eweId, $newLitterDate)
    {
        $checkString = $eweId.self::SEPARATOR.$newLitterDate;
        return array_key_exists($checkString, $this->littersWithDateWithin4MonthsOfOtherLittersInDatabase);
    }


    /**
     * @return array
     */
    private function getLittersWithDateWithin4MonthsOfOtherLittersInDatabase()
    {
        return [
            '913--2001-03-01' => '913--2001-03-01',
            '927--2006-03-21' => '927--2006-03-21',
            '938--2003-02-03' => '938--2003-02-03',
            '973--2006-03-02' => '973--2006-03-02',
            '996--2008-03-15' => '996--2008-03-15',
            '998--2008-03-12' => '998--2008-03-12',
            '6597--1993-02-22' => '6597--1993-02-22',
            '20557--1999-03-29' => '20557--1999-03-29',
            '20557--2002-01-01' => '20557--2002-01-01',
            '29539--2000-03-24' => '29539--2000-03-24',
            '29544--2003-03-19' => '29544--2003-03-19',
            '29545--2004-04-06' => '29545--2004-04-06',
            '40724--2002-03-06' => '40724--2002-03-06',
            '42283--2005-02-28' => '42283--2005-02-28',
            '93499--2011-03-18' => '93499--2011-03-18',
            '176259--2016-03-01' => '176259--2016-03-01',
            '184319--2008-02-28' => '184319--2008-02-28',
            '186994--2010-01-01' => '186994--2010-01-01',
            '207574--2014-04-01' => '207574--2014-04-01',
            '225650--2010-05-14' => '225650--2010-05-14',
            '239998--2011-03-01' => '239998--2011-03-01',
            '258456--2012-03-23' => '258456--2012-03-23',
            '259658--2013-04-03' => '259658--2013-04-03',
            '331009--1985-03-28' => '331009--1985-03-28',
            '331009--1987-03-15' => '331009--1987-03-15',
            '331694--2008-02-28' => '331694--2008-02-28',
            '348261--2014-04-01' => '348261--2014-04-01',
            '438395--2015-03-09' => '438395--2015-03-09',
            '443880--2015-03-01' => '443880--2015-03-01',
            '461542--2015-01-15' => '461542--2015-01-15',
        ];
    }
}