<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BlindnessFactor;
use AppBundle\Entity\BlindnessFactorRepository;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Predicate;
use AppBundle\Entity\PredicateRepository;
use AppBundle\Entity\Race;
use AppBundle\Enumerator\Specie;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigrateVsm2016novCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate vsm import files until 2016 nov';
    const DEFAULT_OPTION = 0;
    const BATCH_SIZE = 1000;

    const DEVELOPER_PRIMARY_KEY = 2151; //Used as the person that creates and edits imported data

    //FileName arrayKeys
    const RACES = 'races';
    const BIRTH = 'birth';
    const ANIMAL_RESIDENCE = 'animal_residence';
    const PERFORMANCE_MEASUREMENTS = 'performance_measurements';
    const ANIMAL_TABLE = 'animal_table';
    const BLINDNESS_FACTOR = 'blindness_factor';
    const MYO_MAX = 'myo_max';
    const TAG_REPLACES = 'tag_replaces';
    const PREDICATES = 'predicates';
    const SUBSCRIPTIONS = 'subscriptions';

    //ArrayConstants
    const PREDICATE_SCORE = 'predicate_score';
    const PREDICATE_VALUE = 'predicate_value';
    const START_DATE = 'start_date';
    const END_DATE = 'end_date';

    /** @var array */
    private $filenames;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/vsm2016nov',
        'finder_out' => 'app/Resources/outputs/migration',
        //'finder_name' => 'filename.csv',
        'ignoreFirstLine' => true
    );

    /** @var ObjectManager $em */
    private $em;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var Employee */
    private $developer;

    /** @var AnimalRepository */
    private $animalRepository;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:vsm2016nov')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->output = $output;
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        /** @var AnimalRepository $repository */
        $this->animalRepository = $this->em->getRepository(Animal::class);

        $this->developer = $em->getRepository(Employee::class)->find(self::DEVELOPER_PRIMARY_KEY);

        $this->filenames = array(
            self::RACES => 'rassen.txt',
            self::BIRTH => '20161007_1156_Diergeboortetabel.csv',
            self::ANIMAL_RESIDENCE => '20161007_1156_Diermutatietabel.csv',
            self::PERFORMANCE_MEASUREMENTS => '20161007_1156_Dierprestatietabel.csv',
            self::ANIMAL_TABLE => '20161007_1156_Diertabel.csv',
            self::BLINDNESS_FACTOR => '20161018_1058_DierBlindfactor.csv',
            self::MYO_MAX => '20161018_1058_DierMyoMax.csv',
            self::TAG_REPLACES => '20161018_1058_DierOmnummeringen.csv',
            self::PREDICATES => '20161019_0854_DierPredikaat_NSFO-correct.csv',
            self::SUBSCRIPTIONS => 'lidmaatschappen_voor_2010.txt',
        );

        //Setup folders if missing
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        NullChecker::createFolderPathsFromArrayIfNull($rootDir, $this->csvParsingOptions);
        
        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: option 1', "\n",
            '2: Migrate Races', "\n",
            '3: Migrate MyoMax', "\n",
            '4: Migrate BlindnessFactor and update values in Animal', "\n",
            '5: Migrate Predicates and update values in Animal', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $output->writeln('DONE!');
                break;

            case 2:
                $result = $this->migrateRaces() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 3:
                $result = $this->migrateMyoMax() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 4:
                $result = $this->migrateBlindnessFactors() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 5:
                $result = $this->migratePredicates() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }


    private function parseCSV($filename) {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];

        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($filename)
        ;
        foreach ($finder as $file) { $csv = $file; }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
                $i++;
                if ($ignoreFirstLine && $i == 1) { continue; }
                $rows[] = $data;
                gc_collect_cycles();
            }
            fclose($handle);
        }

        return $rows;
    }


    /**
     * @return bool
     */
    private function migrateRaces()
    {
        $data = $this->parseCSV($this->filenames[self::RACES]);

        if(count($data) == 0) { return false; }
        else { $this->cmdUtil->setStartTimeAndPrintIt(count($data)+1, 1); }

        $sql = "SELECT full_name FROM race";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $searchArray = [];
        foreach ($results as $result) {
            $fullName = $result['full_name'];
            $searchArray[$fullName] = $fullName;
        }

        $newCount = 0;
        foreach ($data as $record) {

            $fullName = utf8_encode($record[2]);

            if(!array_key_exists($fullName, $searchArray)) {
                //$vsmId = $record[0];
                $abbreviation = $record[1];
                $startDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($record[3]);
                $endDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($record[4]);
                //$createdByString = $record[5];
                //$creationDate = $record[6];
                //$lastUpdatedByString = $record[7];
                //$lastUpdateDate = $record[8];
                $specie = $this->convertSpecieData($record[9]);

                $race = new Race($abbreviation, $fullName);
                $race->setStartDate($startDate);
                $race->setEndDate($endDate);
                $race->setCreatedBy($this->developer);
                $race->setCreationDate(new \DateTime());
                $race->setSpecie($specie);

                $this->em->persist($race);
                $newCount++;
            }
            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->em->flush();
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        return true;
    }

    /**
     * @param string $vsmSpecieData
     * @return string
     */
    private function convertSpecieData($vsmSpecieData){
        return strtr($vsmSpecieData, [ 'SC' => Specie::SHEEP, 'GE' => Specie::GOAT]);
    }
    
    
    private function migrateMyoMax()
    {
        $data = $this->parseCSV($this->filenames[self::MYO_MAX]);

        if(count($data) == 0) { return false; }
        else { $this->cmdUtil->setStartTimeAndPrintIt(count($data)+1, 1); }

        $sql = "SELECT myo_max, name FROM animal WHERE myo_max NOTNULL";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['name']] = $result['myo_max'];
        }

        $newCount = 0;
        foreach ($data as $record) {

            $vsmId = $record[0];

            if (!array_key_exists($vsmId, $searchArray)) {
                $myoMax = $record[1];
                $sql = "UPDATE animal SET myo_max = '".$myoMax."' WHERE name = '".$vsmId."'";
                $this->em->getConnection()->exec($sql);
                $newCount++;
            }
            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        return true;
    }


    /**
     * Note, it has already been checked that, no animal has more than one blindnessFactor
     *
     * @return bool
     */
    private function migrateBlindnessFactors()
    {
        $data = $this->parseCSV($this->filenames[self::BLINDNESS_FACTOR]);

        if(count($data) == 0) { return false; }
        else { $this->cmdUtil->setStartTimeAndPrintIt(count($data)+1, 1); }

        $sql = "SELECT animal_id, log_date, blindness_factor FROM blindness_factor";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $blindnessFactorSearchArray = [];
        foreach ($results as $result) {
            $blindnessFactorSearchArray[$result['animal_id']] = $result['blindness_factor'];
        }
        $animalIdByVsmIdSearchArray = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $newCount = 0;
        foreach ($data as $record) {

            $vsmId = $record[0];

            if(array_key_exists($vsmId, $animalIdByVsmIdSearchArray)) {

                $animalId = $animalIdByVsmIdSearchArray[$vsmId];

                if (!array_key_exists($animalId, $blindnessFactorSearchArray)) {
                    $blindnessFactorValue = Translation::getEnglish($record[1]);
                    $logDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($record[2]);
                    /** @var Animal $animal */
                    $animal = $this->animalRepository->find($animalId);
                    $blindnessFactor = new BlindnessFactor($animal, $blindnessFactorValue, $logDate);
                    $this->em->persist($blindnessFactor);
                    $newCount++;
                }
            }
            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->em->flush();
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        $this->updateBlindnessFactorValuesInAnimal();

        return true;
    }
    

    private function updateBlindnessFactorValuesInAnimal()
    {
        /** @var BlindnessFactorRepository $repository */
        $repository = $this->em->getRepository(BlindnessFactor::class);
        $repository->setLatestBlindnessFactorsOnAllAnimals($this->cmdUtil);
    }


    /**
     * Note, it has already been checked that, no animal has more than one blindnessFactor
     *
     * @return bool
     */
    private function migratePredicates($useSql = true)
    {
        $data = $this->parseCSV($this->filenames[self::PREDICATES]);

        if(count($data) == 0) { return false; }
        else { $this->cmdUtil->setStartTimeAndPrintIt(count($data)+1, 1); }

        //1. Create predicateSearchArrays with latest startDates
        $latestStartDateSearchArray = new ArrayCollection();
        $latestCsvPredicatesByAnimalId = new ArrayCollection();

        $animalIdByVsmIdSearchArray = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        foreach ($data as $records) {

            $vsmId = $records[0];
            //Skip data for missing animals
            if(array_key_exists($vsmId, $animalIdByVsmIdSearchArray)) {
                $animalId = $animalIdByVsmIdSearchArray[$vsmId];
                $startDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($records[1]);
                $endDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($records[2]);
                $predicateScore = $records[3] != '' ? $records[3] : null;
                $predicateValue = $records[4];

                $isLatestRecord = false;
                if($latestStartDateSearchArray->containsKey($animalId)) {
                    $latestStartDate = $latestStartDateSearchArray->get($animalId);

                    if($latestStartDate == null && $startDate != null) {
                        $latestStartDateSearchArray->set($animalId, $startDate);
                        $isLatestRecord = true;
                    } else if($startDate >= $latestStartDate) {
                        $latestStartDateSearchArray->set($animalId, $startDate);
                        $isLatestRecord = true;
                    }
                } else {
                    $latestStartDateSearchArray->set($animalId, $startDate);
                    $isLatestRecord = true;
                }

                if($isLatestRecord) {
                    $latestCsvPredicatesByAnimalId->set($animalId,
                        [ self::START_DATE => $startDate,
                            self::END_DATE => $endDate,
                            self::PREDICATE_SCORE => $predicateScore,
                            self::PREDICATE_VALUE => $predicateValue,
                        ]);
                }
            }
        }

        //2. Get searchArrays of current data
        $sql = "SELECT animal_id, start_date, end_date, predicate, predicate_score FROM predicate";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $predicatesFromDbSearchArray = new ArrayCollection();
        foreach ($results as $result) {

            $animalId = $result['animal_id'];
            $startDate = TimeUtil::getDateTimeFromNullCheckedDateString($result['start_date']);
            $endDate = TimeUtil::getDateTimeFromNullCheckedDateString($result['end_date']);
            $predicateScore = $result['predicate_score'];
            $predicateValue = $result['predicate'];

            $predicatesFromDbSearchArray->set($animalId,
                [ self::START_DATE => $startDate,
                    self::END_DATE => $endDate,
                    self::PREDICATE_SCORE => $predicateScore,
                    self::PREDICATE_VALUE => $predicateValue,
                ]);
        }
        

        $newCount = 0;
        $totalCount = 0;
        $animalIds = $latestCsvPredicatesByAnimalId->getKeys();
        foreach ($animalIds as $animalId) {

            $csvPredicateData = $latestCsvPredicatesByAnimalId->get($animalId);
            $csvStartDate = $csvPredicateData[self::START_DATE];
            $csvEndDate = $csvPredicateData[self::END_DATE];
            $csvPredicateScore = $csvPredicateData[self::PREDICATE_SCORE];
            $csvPredicateValue = $csvPredicateData[self::PREDICATE_VALUE];

            $persistNewPredicate = true;
            if($predicatesFromDbSearchArray->containsKey($animalId)) {
                $dbPredicateData = $predicatesFromDbSearchArray->get($animalId);
                $dbStartDate = $dbPredicateData[self::START_DATE];
                $dbEndDate = $dbPredicateData[self::END_DATE];
                $dbPredicateScore = $dbPredicateData[self::PREDICATE_SCORE];
                $dbPredicateValue = $dbPredicateData[self::PREDICATE_VALUE];

                if($csvStartDate < $dbStartDate) {
                    $persistNewPredicate = false;
                } elseif ($csvStartDate == $dbStartDate && $csvPredicateScore == $dbPredicateScore
                        && $csvPredicateValue == $dbPredicateValue) {
                    $persistNewPredicate = false;
                }
            }


            if($persistNewPredicate) {

                $csvStartDateString = TimeUtil::getTimeStampForSql($csvStartDate);
                $csvEndDateString = TimeUtil::getTimeStampForSql($csvEndDate);


                if($useSql) {
                    $csvStartDateString = $csvStartDateString == null ? 'NULL' : "'".$csvStartDateString."'";
                    $csvEndDateString = $csvEndDateString == null ? 'NULL' : "'".$csvEndDateString."'";
                    $csvPredicateScore = $csvPredicateScore == null ? 'NULL' : $csvPredicateScore;

                    $sql = "INSERT INTO predicate (id, animal_id, start_date, end_date, predicate, predicate_score) VALUES (nextval('measurement_id_seq')," . $animalId . "," . $csvStartDateString . "," . $csvEndDateString . ",'" . $csvPredicateValue . "'," . $csvPredicateScore . ")";
                    $this->em->getConnection()->exec($sql);

                } else {
                    /** @var Animal $animal */
                    $animal = $this->animalRepository->find($animalId);

                    $predicate = new Predicate();
                    $predicate->setAnimal($animal);
                    $predicate->setStartDate($csvStartDate);
                    $predicate->setEndDate($csvEndDate);
                    $predicate->setPredicate($csvPredicateValue);
                    $predicate->setPredicateScore($csvPredicateScore);

                    $this->em->persist($predicate);
                }
                $newCount++;
            }
            $totalCount++;
            $this->cmdUtil->advanceProgressBar(1);

            if(!$useSql) {
                if($totalCount%self::BATCH_SIZE == 0) { $this->em->flush(); }
            }
        }
        if(!$useSql) { $this->em->flush(); }
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        $this->updatePredicateValuesInAnimal();

        return true;
    }
    
    
    private function updatePredicateValuesInAnimal()
    {
        /** @var PredicateRepository $repository */
        $repository = $this->em->getRepository(Predicate::class);
        $repository->setLatestPredicateValuesOnAllAnimals($this->cmdUtil);
        $repository->fillPredicateValuesInAnimalForPredicatesWithoutStartDates($this->cmdUtil);
    }
}
