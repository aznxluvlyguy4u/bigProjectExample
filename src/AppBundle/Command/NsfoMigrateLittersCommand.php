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
    const DEFAULT_OPTION = 0;

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

    /** @var array */
    private $dataWithoutHeader;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var ObjectManager $em */
    private $em;

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

        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $this->animalPrimaryKeysByVsmId = $this->eweRepository->getAnimalPrimaryKeysByVsmId();

        //Input folder input
        $inputFolderPath = $this->cmdUtil->generateQuestion('Please enter input folder path (empty for default path)', self::DEFAULT_INPUT_PATH);
        $this->dataWithoutHeader = CommandUtil::getRowsFromCsvFileWithoutHeader($inputFolderPath);


        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            'Generate Litters from source file (incl mother, excl children) (1)', "\n",
            'Match children with existing litters and set father in litter (2)', "\n",
            'Find missing father in animal by searching in litter (3)', "\n",
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

            default:
                $output->writeln('ABORTED');
                break;
        }

    }


    private function findMissingFathersForAnimalFromLitter()
    {
        //Retrieve data from db
        $totalMissingFathersStart = $this->getMissingFathersCount();

        $sql = "SELECT id as animal_id, animal.litter_id as animal_litter_id FROM animal WHERE animal.litter_id IS NOT NULL AND animal.parent_father_id IS NULL";
        $childrenResults = $this->em->getConnection()->query($sql)->fetchAll();

        $sql = "SELECT animal_father_id, id as litter_id FROM litter WHERE animal_father_id IS NOT NULL";
        $litterResults = $this->em->getConnection()->query($sql)->fetchAll();

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
                $this->em->getConnection()->exec($sql);

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
        $isSkipChildrenWithALitter = $this->cmdUtil->generateConfirmationQuestion('Skip children with a litter? (y/n)');

        //Retrieve data from db
        if ($isSkipChildrenWithALitter) {
            $sql = "SELECT date_of_birth, id as animal_id, animal.litter_id as animal_litter_id, parent_mother_id, parent_father_id FROM animal WHERE animal.litter_id IS NOT NULL";
        } else {
            $sql = "SELECT date_of_birth, id as animal_id, animal.litter_id as animal_litter_id, parent_mother_id, parent_father_id FROM animal";
        }
        $childrenResults = $this->em->getConnection()->query($sql)->fetchAll();

        $sql = "SELECT animal_mother_id, litter.litter_date as litter_date, id as litter_id FROM litter";
        $litterResults = $this->em->getConnection()->query($sql)->fetchAll();

        $this->cmdUtil->setStartTimeAndPrintIt(count($childrenResults), 1, 'Data retrieved from database. Now matching Children with Litters...');

        //Create search arrays
        $animalSearchArray = new ArrayCollection();
        $litterSearchArray = new ArrayCollection();

        foreach ($childrenResults as $childResult) {
            $motherDateString = $this->createAnimalSearchString($childResult);
            $animalSearchArray->set($motherDateString, $childResult);
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
            $childResult = $animalSearchArray->get($animalMotherDateString);
            $animalId = $childResult['animal_id'];
            $fatherId = $childResult['parent_father_id'];
            if ($litterId != null) {

                $sql = "UPDATE animal SET litter_id = '" . $litterId . "' WHERE id = '" . $animalId . "'";
                $this->em->getConnection()->exec($sql);

                if ($fatherId != null) {
                    $sql = "UPDATE litter SET animal_father_id = '" . $fatherId . "' WHERE id = '" . $litterId . "'";
                    $this->em->getConnection()->exec($sql);
                    $missingFatherCount++;
                }

                $foundLittersCount++;
                $animalProgressBarMessage = 'LITTER FOUND FOR ANIMAL (id): '.$animalId;
            } else {
                $noLitterFoundCount++;
                $animalProgressBarMessage = 'MISSING LITTER FOR ANIMAL (id): '.$animalId;
            }
            $this->cmdUtil->advanceProgressBar(1, $animalProgressBarMessage);
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

        return $motherId . '__' . $dateTime;
    }


    /**
     * @param array $litterResult
     * @return string
     */
    private function createLitterSearchString($litterResult)
    {
        $dateTime = substr($litterResult['litter_date'], 0, 10);
        $motherId = $litterResult['animal_mother_id'];

        return $motherId . '__' . $dateTime;
    }


    private function migrateLitters()
    {
        $minEweId = $this->cmdUtil->generateQuestion('Please enter minimum ewe primaryKey (default = 1)', self::DEFAULT_MIN_EWE_ID);

        $this->cmdUtil->setStartTimeAndPrintIt(count($this->dataWithoutHeader) * 2, $minEweId);

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
        $this->cmdUtil->setProgressBarMessage('Ewes to process: ' . sizeof($eweIds) . ' | Creating new litters...');

        $litterCount = 0;
        $eweCount = 0;

        $today = new \DateTime('today');
        $todayString = $today->format('Y-m-d');

        /** @var LitterRepository $litterRepository */
        $litterRepository = $this->em->getRepository(Litter::class);

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

                        $sql = "SELECT MAX(id) FROM litter";
                        $result = $this->em->getConnection()->query($sql)->fetch();
                        $litterId = $result['max'] + 1;

//                      Litter data has not been migrated yet, so persist a new litter
                        $sql = "INSERT INTO litter (id, animal_mother_id, log_date, litter_date, stillborn_count, born_alive_count) VALUES ('" . $litterId . "','" . $eweId . "','" . $todayString . "','" . $litterDateString . "','" . $stillbornCount . "','" . $bornAliveCount . "')";
                        $this->em->getConnection()->exec($sql);
                        $litterCount++;
                    }

                    $this->cmdUtil->advanceProgressBar(1, 'Checked LitterCount: ' . $litterCount . ' |  EweCount: ' . $eweCount . ' |  last Id: ' . $eweId);
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
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0] . ' 00:00:00';
        }
    }


    /**
     * @param int $eweId
     * @return bool
     */
    private function isEweExists($eweId)
    {
        $sql = "SELECT id FROM ewe WHERE id = '" . $eweId . "'";
        $result = $this->em->getConnection()->query($sql)->fetch();
        if ($result['id'] != null) {
            return true;
        } else {
            return false;
        }
    }


    private function isLitterAlreadyExists($eweId, $measurementDateString)
    {
        $sql = "SELECT id FROM litter WHERE animal_mother_id = '" . $eweId . "' AND litter_date = '" . $measurementDateString . "'";
        $result = $this->em->getConnection()->query($sql)->fetch();
        if ($result['id'] != null) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @return int
     */
    private function getMissingFathersCount()
    {
        $sql = "SELECT COUNT(id) FROM animal WHERE animal.parent_father_id IS NULL";
        return $this->em->getConnection()->query($sql)->fetch()['count'];
    }
}