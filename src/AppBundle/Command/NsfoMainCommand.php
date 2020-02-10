<?php

namespace AppBundle\Command;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Cache\BreedValuesResultTableUpdater;
use AppBundle\Cache\ExteriorCacher;
use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Cache\NLingCacher;
use AppBundle\Cache\ProductionCacher;
use AppBundle\Cache\TailLengthCacher;
use AppBundle\Cache\WeightCacher;
use AppBundle\Component\AsciiArt;
use AppBundle\Component\MixBlup\MixBlupInputFileValidator;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\EditType;
use AppBundle\Entity\Location;
use AppBundle\Entity\ProcessLog;
use AppBundle\Entity\ScrapieGenotypeSource;
use AppBundle\Entity\TagSyncErrorLog;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Enumerator\Country;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Enumerator\PedigreeAbbreviation;
use AppBundle\Enumerator\ProcessType;
use AppBundle\Service\BreedIndexService;
use AppBundle\Service\BreedValuePrinter;
use AppBundle\Service\BreedValueService;
use AppBundle\Service\CacheService;
use AppBundle\Service\DataFix\DuplicateMeasurementsFixer;
use AppBundle\Service\ExcelService;
use AppBundle\Service\InbreedingCoefficient\InbreedingCoefficientUpdaterService;
use AppBundle\Service\Migration\LambMeatIndexMigrator;
use AppBundle\Service\Migration\MixBlupAnalysisTypeMigrator;
use AppBundle\Service\Migration\WormResistanceIndexMigrator;
use AppBundle\Service\MixBlupInputFilesService;
use AppBundle\Service\MixBlupOutputFilesService;
use AppBundle\Service\ProcessLockerInterface;
use AppBundle\Service\Report\BreedValuesOverviewReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DatabaseDataFixer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\ErrorLogUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\MainCommandUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AscendantValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NsfoMainCommand
 */
class NsfoMainCommand extends ContainerAwareCommand
{
    const TITLE = 'OVERVIEW OF ALL NSFO COMMANDS';

    const DEFAULT_OPTION = 0;
    const DEFAULT_LOCATION_ID = 262;
    const DEFAULT_UBN = 1674459;

    const DEFAULT_MIN_UBN = 0;
    const DEFAULT_GENERATION_DATE_STRING = "2017-01-01 00:00:00";

    const MAIN_TITLE = 'OVERVIEW OF ALL NSFO COMMANDS';
    const ANIMAL_CACHE_TITLE = 'UPDATE ANIMAL CACHE / RESULT TABLE VALUES';
    const LITTER_GENE_DIVERSITY_TITLE = 'UPDATE LITTER AND GENE DIVERSITY VALUES';
    const ERROR_LOG_TITLE = 'ERROR LOG COMMANDS';
    const FIX_DUPLICATE_ANIMALS = 'FIX DUPLICATE ANIMALS';
    const FIX_DATABASE_VALUES = 'FIX DATABASE VALUES';
    const INFO_SYSTEM_SETTINGS = 'NSFO SYSTEM SETTINGS';
    const INITIALIZE_DATABASE_VALUES = 'INITIALIZE DATABASE VALUES';
    const FILL_MISSING_DATA = 'FILL MISSING DATA';
    const GENDER_CHANGE = 'GENDER CHANGE';

    const LINE_THICK = "========================================================================";
    const LINE_THIN = '-----------------------------------------------';
    const TYPES_EXCLUDING_PREREQUISITES = ' types (excluding prerequisites)';

    /** @var ObjectManager|EntityManagerInterface */
    private $em;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var Connection */
    private $conn;
    /** @var bool */
    private $exitAfterRun = false;

    protected function configure()
    {
        $this
            ->setName('nsfo')
            ->setDescription(self::TITLE)
            ->addOption('option', 'o', InputOption::VALUE_OPTIONAL,
                'Run process directly. For example: 6,1')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cmdUtil = new CommandUtil($input, $output, $this->getHelper('question'));

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->conn = $this->em->getConnection();

        $selectedOptions = MainCommandUtil::getSelectedOptions($input);
        if (empty($selectedOptions)) {
            $this->mainMenu(true);
        } else {
            $this->mainMenu(true, $selectedOptions);
        }
    }


    /**
     * @param string $title
     */
    private function initializeMenu($title)
    {
        $this->cmdUtil->printTitle($title);

        $this->printDbInfo();
    }


    /**
     * Generated using ascii generator: http://glassgiant.com/ascii/
     */
    public function printAsciiArt()
    {
        $this->cmdUtil->writeln(AsciiArt::nsfoLogo());
    }


    /**
     * @param bool $isIntroScreen
     * @param array $options
     */
    public function mainMenu($isIntroScreen = true, array $options = [])
    {
        if ($isIntroScreen) {
            $this->printAsciiArt();
        }

        $this->initializeMenu(self::MAIN_TITLE);

        if (empty($options)) {
            $option = $this->cmdUtil->generateMultiLineQuestion([
                self::LINE_THICK, "\n",
                'SELECT SUBMENU: ', "\n",
                self::LINE_THICK, "\n",
                '1: '.strtolower(self::INFO_SYSTEM_SETTINGS), "\n",
                self::LINE_THIN, "\n",
                '2: '.strtolower(self::ANIMAL_CACHE_TITLE), "\n",
                '3: '.strtolower(self::LITTER_GENE_DIVERSITY_TITLE), "\n",
                '4: '.strtolower(self::ERROR_LOG_TITLE), "\n",
                '5: '.strtolower(self::FIX_DUPLICATE_ANIMALS), "\n",
                MainCommandUtil::FIX_DATABASE_VALUES.': '.strtolower(self::FIX_DATABASE_VALUES), "\n",
                '7: '.strtolower(self::GENDER_CHANGE), "\n",
                MainCommandUtil::INITIALIZE_DATABASE_VALUES.': '.strtolower(self::INITIALIZE_DATABASE_VALUES), "\n",
                '9: '.strtolower(self::FILL_MISSING_DATA), "\n",
                self::LINE_THIN, "\n",
                '10: '.strtolower(CommandTitle::DATA_MIGRATION), "\n",
                self::LINE_THIN, "\n",
                '11: '.strtolower(CommandTitle::MIXBLUP), "\n",
                self::LINE_THIN, "\n",
                '12: '.strtolower(CommandTitle::DEPART_INTERNAL_WORKER), "\n",
                self::LINE_THIN, "\n",
                '13: '.strtolower(CommandTitle::CALCULATIONS_AND_ALGORITHMS), "\n",
                self::LINE_THIN, "\n",
                MainCommandUtil::PROCESSOR_LOCKER_OPTIONS.': '.strtolower(CommandTitle::PROCESS_LOCKER), "\n",
                self::LINE_THICK, "\n",
                '15: '.strtolower(CommandTitle::REDIS), "\n",
                self::LINE_THICK, "\n",
                'other: EXIT ', "\n"
            ], self::DEFAULT_OPTION);
        } else {
            $option = array_shift($options);
        }

        switch ($option) {
            case 1: $this->getContainer()->get('app.info.parameters')->printInfo(); break;

            case 2: $this->animalCacheOptions(); break;
            case 3: $this->litterAndGeneDiversityOptions(); break;
            case 4: $this->errorLogOptions(); break;
            case 5: $this->fixDuplicateAnimalsOptions(); break;
            case MainCommandUtil::FIX_DATABASE_VALUES: $this->fixDatabaseValuesOptions($options); break;
            case 7: $this->getContainer()->get('app.cli.gender_changer')->run($this->cmdUtil); break;
            case MainCommandUtil::INITIALIZE_DATABASE_VALUES: $this->initializeDatabaseValuesOptions($options); break;
            case 9: $this->fillMissingDataOptions($options); break;
            case 10: $this->dataMigrationOptions(); break;
            case 11: $this->runMixblupCliOptions($this->cmdUtil); break;
            case 12: $this->getContainer()->get('app.cli.internal_worker.depart')->run($this->cmdUtil); break;
            case 13: $this->calculationsAndAlgorithmsOptions($options); break;
            case MainCommandUtil::PROCESSOR_LOCKER_OPTIONS: $this->processLockerOptions($options); break;
            case 15: $this->redisOptions(); break;

            default: return;
        }
        $this->mainMenu(false);
    }


    public function animalCacheOptions()
    {
        $this->initializeMenu(self::ANIMAL_CACHE_TITLE);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '--- All & By Location ---', "\n",
            '1: Generate new AnimalCache records for all animals that do not have one yet', "\n",
            '2: Generate new AnimalCache records only for given locationId', "\n",
            '3: Regenerate all AnimalCache records for all animals', "\n",
            '4: Regenerate AnimalCache records only for given locationId', "\n",
            '5: Regenerate all AnimalCache records older than given stringDateTime (YYYY-MM-DD HH:MM:SS)', "\n",
            '6: Generate all AnimalCache records for animal and ascendants (3gen) for given locationId', "\n",
            '7: Regenerate all AnimalCache records for animal and ascendants (3gen) for given locationId', "\n",
            '8: Delete duplicate records', "\n",
            '9: Update location_of_birth_id for all animals and locations', "\n",
            '--- Location Focused ---', "\n",
            '11: Update AnimalCache of one Animal by animalId', "\n",
            '12: Generate new AnimalCache records for all animals, batched by location and ascendants', "\n",
            '--- Sql Batch Queries ---', "\n",
            '20: BatchUpdate all incongruent production values and n-ling values', "\n",
            '21: BatchUpdate all Incongruent exterior values', "\n",
            '22: BatchUpdate all Incongruent weight values', "\n",
            '23: BatchUpdate all Incongruent tailLength values', "\n",
            self::LINE_THIN, "\n",
            '24: BatchInsert empty animal_cache records and BatchUpdate all Incongruent values', "\n",
            '25: Remove all orphaned animal_cache records', "\n",
            '', "\n",
            '--- Helper Commands ---', "\n",
            '99: Get locationId from UBN', "\n",

            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                AnimalCacher::cacheAnimalsBySqlInsert($this->em, $this->cmdUtil);
                $this->cmdUtil->writeln('DONE!');
                break;

            case 2:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsBySqlInsert($this->em, $this->cmdUtil, $locationId);
                $this->cmdUtil->writeln('DONE!');
                break;

            case 3:
                AnimalCacher::cacheAllAnimals($this->em, $this->cmdUtil, false);
                $this->cmdUtil->writeln('DONE!');
                break;

            case 4:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsOfLocationId($this->em, $locationId, $this->cmdUtil, false);
                $this->cmdUtil->writeln('DONE!');
                break;

            case 5:
                $todayDateString = TimeUtil::getTimeStampToday().' 00:00:00';
                $dateString = intval($this->cmdUtil->generateQuestion('insert dateTimeString (default = '.$todayDateString.')', $todayDateString));
                AnimalCacher::cacheAllAnimals($this->em, $this->cmdUtil, false, $dateString);
                $this->cmdUtil->writeln('DONE!');
                break;

            case 6:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsAndAscendantsByLocationId($this->em, true, null, $this->cmdUtil, $locationId);
                $this->cmdUtil->writeln('DONE!');
                break;

            case 7:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsAndAscendantsByLocationId($this->em, false, null, $this->cmdUtil, $locationId);
                $this->cmdUtil->writeln('DONE!');
                break;

            case 8:
                AnimalCacher::deleteDuplicateAnimalCacheRecords($this->em, $this->cmdUtil);
                $this->cmdUtil->writeln('DONE!');
                break;

            case 9:
                $this->em->getRepository(Animal::class)->updateAllLocationOfBirths($this->cmdUtil);
                $this->cmdUtil->writeln('DONE!');
                break;


            case 11:
                $this->cacheOneAnimalById();
                $this->cmdUtil->writeln('DONE!');
                break;

            case 12:
                AnimalCacher::cacheAllAnimalsByLocationGroupsIncludingAscendants($this->em, $this->cmdUtil);
                $this->cmdUtil->writeln('DONE!');
                break;


            case 20:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update production and n-ling cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $this->cmdUtil->writeln('Updating all records...');
                    $productionValuesUpdated = ProductionCacher::updateAllProductionValues($this->conn);
                    $nLingValuesUpdated = NLingCacher::updateAllNLingValues($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));
                    $productionValuesUpdated = ProductionCacher::updateProductionValues($this->conn, [$animalId]);
                    $nLingValuesUpdated = NLingCacher::updateNLingValues($this->conn, [$animalId]);
                }
                $this->cmdUtil->writeln($productionValuesUpdated.' production values updated');
                $this->cmdUtil->writeln($nLingValuesUpdated.' n-ling values updated');
                break;


            case 21:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update exterior cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $this->cmdUtil->writeln('Updating all records...');
                    $updateCount = ExteriorCacher::updateAllExteriors($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = ExteriorCacher::updateExteriors($this->conn, [$animalId]);
                }
                $this->cmdUtil->writeln([$updateCount.' exterior animalCache records updated' ,'DONE!']);
                break;


            case 22:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update weight cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $this->cmdUtil->writeln('Updating all records...');
                    $updateCount = WeightCacher::updateAllWeights($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = WeightCacher::updateWeights($this->conn, [$animalId]);
                }
                $this->cmdUtil->writeln([$updateCount.' weight animalCache records updated' ,'DONE!']);
                break;

            case 23:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update tailLength cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $this->cmdUtil->writeln('Updating all records...');
                    $updateCount = TailLengthCacher::updateAll($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = TailLengthCacher::update($this->conn, [$animalId]);
                }
                $this->cmdUtil->writeln([$updateCount.' tailLength animalCache records updated' ,'DONE!']);
                break;

            case 24: AnimalCacher::cacheAllAnimalsBySqlBatchQueries($this->conn, $this->cmdUtil); break;

            case 25:
                $updateCount = AnimalCacher::removeAllOrphanedRecords($this->conn);
                $this->writeLn((empty($updateCount) ? 'No' : $updateCount).' orphaned animalCache records removed');
                break;

            case 99:
                $this->printLocationIdFromGivenUbn();
                $this->cmdUtil->writeln('DONE!');
                break;

            default: $this->writeMenuExit(); return;
        }
        $this->animalCacheOptions();
    }


    private function writeMenuExit() {
        $this->writeLn('Exit menu');
    }


    private function printLocationIdFromGivenUbn()
    {
        do {
            $ubn = $this->cmdUtil->generateQuestion('Insert UBN (default = '.self::DEFAULT_UBN.')', self::DEFAULT_UBN);
        } while (!ctype_digit($ubn) && !is_int($ubn));

        $result = $this->conn->query("SELECT id, is_active FROM location WHERE ubn = '".$ubn."' ORDER BY is_active DESC LIMIT 1")->fetch();


        if($result) {
            $isActiveText = ArrayUtil::get('is_active', $result) ? 'ACTIVE' : 'NOT ACTIVE';
            $this->cmdUtil->writeln('locationId: ' . ArrayUtil::get('id', $result) .' ('. $isActiveText.')');
        } else {
            $this->cmdUtil->writeln('NO LOCATION');
        }

    }


    private function cacheOneAnimalById()
    {
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->em->getRepository(Animal::class);

        do {
            $animal = null;
            $animalId = $this->cmdUtil->generateQuestion('Insert animalId', null);

            if(ctype_digit($animalId) || is_int($animalId)) {
                /** @var Animal $animal */
                $animal = $animalRepository->find($animalId);

                if($animal == null) { $this->cmdUtil->writeln('No animal found for given id: '.$animalId); }
            } else {
                $this->cmdUtil->writeln('AnimalId '.$animalId.' is incorrect. It must be an integer.');
            }

        } while ($animal == null);

        AnimalCacher::cacheByAnimal($this->em, $animal);
    }


    public function litterAndGeneDiversityOptions()
    {
        $this->initializeMenu(self::LITTER_GENE_DIVERSITY_TITLE);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '--- Non AnimalCache Sql Batch Queries ---   ', "\n",
            '1: BatchUpdate heterosis and recombination values, non-updated only', "\n",
            '2: BatchUpdate heterosis and recombination values, regenerate all', "\n\n",
            '3: BatchUpdate match Mates and Litters, non-updated only', "\n",
            '4: BatchUpdate match Mates and Litters, regenerate all', "\n",
            '5: BatchUpdate remove Mates from REVOKED Litters', "\n",
            '6: BatchUpdate count Mates and Litters to be matched', "\n\n",
            '7: BatchUpdate suckleCount in Litters, update all incongruous values', "\n",
            '8: BatchUpdate remove suckleCount from REVOKED Litters', "\n\n",
            '9: BatchUpdate litterOrdinals in Litters, update all incongruous values', "\n",
            '10: BatchUpdate remove litterOrdinals from REVOKED Litters', "\n\n",
            '11: BatchUpdate cumulativeBornAliveCount in Litters, update all incongruous values. NOTE! Update litterOrdinals first!', "\n",
            '12: BatchUpdate gestationPeriods in Litters, update all incongruous values (incl. revoked litters and mates)', "\n",
            '13: BatchUpdate birthIntervals in Litters, update all incongruous values (incl. revoked litters and mates NOTE! Update litterOrdinals first!)', "\n\n",

            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1: GeneDiversityUpdater::updateAll($this->conn, false, $this->cmdUtil); break;
            case 2: GeneDiversityUpdater::updateAll($this->conn, true, $this->cmdUtil); break;
            case 3: $this->writeLn(LitterUtil::matchMatchingMates($this->conn, false).' \'mate-litter\'s matched'); break;
            case 4: $this->writeLn(LitterUtil::matchMatchingMates($this->conn, true).' \'mate-litter\'s matched'); break;
            case 5: $this->writeLn(LitterUtil::removeMatesFromRevokedLitters($this->conn).' \'mate-litter\'s unmatched'); break;
            case 6: $this->writeLn(LitterUtil::countToBeMatchedLitters($this->conn).' \'mate-litter\'s to be matched'); break;
            case 7: $this->writeLn(LitterUtil::updateSuckleCount($this->conn).' suckleCounts updated'); break;
            case 8: $this->writeLn(LitterUtil::removeSuckleCountFromRevokedLitters($this->conn).' suckleCounts removed from revoked litters'); break;
            case 9: $this->writeLn(LitterUtil::updateLitterOrdinals($this->conn).' litterOrdinals updated'); break;
            case 10: $this->writeLn(LitterUtil::removeLitterOrdinalFromRevokedLitters($this->conn).' litterOrdinals removed from revoked litters'); break;
            case 11: $this->writeLn(LitterUtil::updateCumulativeBornAliveCount($this->conn).' cumulativeBornAliveCount updated'); break;
            case 12: $this->writeLn(LitterUtil::updateGestationPeriods($this->conn).' gestationPeriods updated'); break;
            case 13: $this->writeLn(LitterUtil::updateBirthInterVal($this->conn).' birthIntervals updated'); break;

            default: $this->writeMenuExit(); return;
        }
        $this->litterAndGeneDiversityOptions();
    }


    public function errorLogOptions()
    {
        $this->initializeMenu(self::ERROR_LOG_TITLE);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Update TagSyncErrorLog records isFixed status', "\n",
            '2: List animalSyncs with tags blocked by existing animals', "\n",
            '3: Get sql filter query by RetrieveAnimalId', "\n\n",

            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $updateCount = ErrorLogUtil::updateTagSyncErrorLogIsFixedStatuses($this->conn);
                $this->cmdUtil->writeln($updateCount. ' TagSyncErrorLog statuses updated');
                break;

            case 2:
                $this->cmdUtil->writeln(['retrieveAnimalsId' => 'blockingAnimalsCount']);
                $this->cmdUtil->writeln($this->em->getRepository(TagSyncErrorLog::class)->listRetrieveAnimalIds());
                break;

            case 3:
                $retrieveAnimalsId = $this->requestRetrieveAnimalsId();
                $this->cmdUtil->writeln($this->em->getRepository(TagSyncErrorLog::class)->getQueryFilterByRetrieveAnimalIds($retrieveAnimalsId));
                break;

            default: $this->writeMenuExit(); return;
        }
        $this->errorLogOptions();
    }


    /**
     * @return string
     */
    private function requestRetrieveAnimalsId()
    {
        $listRetrieveAnimalsId = $this->em->getRepository(TagSyncErrorLog::class)->listRetrieveAnimalIds();
        do {
            $this->cmdUtil->writeln('Valid RetrieveAnimalsIds by blockingAnimalsCount:');
            $this->cmdUtil->writeln($listRetrieveAnimalsId);
            $this->cmdUtil->writeln('-------------');
            $retrieveAnimalsId = $this->cmdUtil->generateQuestion('Insert RetrieveAnimalsId', 0);

            $isInvalidRetrieveAnimalsId = !key_exists($retrieveAnimalsId, $listRetrieveAnimalsId);
            if($isInvalidRetrieveAnimalsId) {
                $this->cmdUtil->writeln('Inserted RetrieveAnimalsId is invalid!');
            }

        } while($isInvalidRetrieveAnimalsId);
        return $retrieveAnimalsId;
    }


    public function fixDuplicateAnimalsOptions()
    {
        $this->initializeMenu(self::FIX_DUPLICATE_ANIMALS);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: Fix duplicate animals, near identical including duplicate vsmId', "\n",
            '2: Fix duplicate animals, synced I&R vs migrated animals', "\n",
            '3: Merge two animals by primaryKeys', "\n",
            '4: Merge two animals where one is missing leading zeroes', "\n",
            '5: Fix duplicate animals due to tagReplace error', "\n",
            '6: Fix duplicate animals due to tagReplace and animalSync race condition', "\n",
            '7: Merge two animals by uln (primary) and pedigreeNumber (secondary) csv correction file', "\n",
            '8: Merge two animals by ulns in correction file and create tag replace', "\n\n",
            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        $duplicateAnimalsFixer = $this->getContainer()->get('app.datafix.animals.duplicate');

        switch ($option) {
            case 1: $duplicateAnimalsFixer->fixDuplicateAnimalsGroupedOnUlnVsmIdDateOfBirth($this->cmdUtil); break;
            case 2: $duplicateAnimalsFixer->fixDuplicateAnimalsSyncedAndImportedPairs($this->cmdUtil); break;
            case 3: $duplicateAnimalsFixer->mergeAnimalPairs($this->cmdUtil); break;
            case 4: $duplicateAnimalsFixer->mergeImportedAnimalsMissingLeadingZeroes($this->cmdUtil); break;
            case 5: $duplicateAnimalsFixer->fixDuplicateDueToTagReplaceError($this->cmdUtil); break;
            case 6: $duplicateAnimalsFixer->fixDuplicateDueToTagReplaceAndAnimalSyncRaceCondition($this->cmdUtil); break;
            case 7: $duplicateAnimalsFixer->mergePrimaryUlnWithSecondaryPedigreeNumberFromCsvFile($this->cmdUtil); break;
            case 8: $duplicateAnimalsFixer->mergeByUlnStringsAndCreateDeclareTagReplace($this->cmdUtil); break;
            default: $this->writeMenuExit(); return;
        }
        $this->fixDuplicateAnimalsOptions();
    }


    public function fixDatabaseValuesOptions(array $options = [])
    {
        $this->initializeMenu(self::FIX_DATABASE_VALUES);

        if (empty($options)) {
            $option = $this->cmdUtil->generateMultiLineQuestion([
                'Choose option: ', "\n",
                self::LINE_THICK, "\n",
                '1: Update MaxId of all sequences', "\n",
                self::LINE_THICK, "\n",
                '2: Fix incongruent genders vs Ewe/Ram/Neuter records', "\n",
                '3: Fix incongruent animalOrderNumbers', "\n",
                '4: Fix incongruent animalIdAndDate values in measurement table', "\n",
                '5: Fix duplicate litters only containing stillborns', "\n",
                '6: Find animals with themselves being their own ascendant', "\n",
                '7: Print from database, animals with themselves being their own ascendant', "\n",
                '8: Fill missing breedCodes and set breedCode = breedCodeParents if both parents have the same pure (XX100) breedCode', "\n",
                '9: Replace non-alphanumeric symbols in uln_number of animal table (based on symbols found in migration file)', "\n",
                '10: Replace non-digit symbols in ubn_of_birth of animal table ', "\n",
                '11: Replace non-digit symbols in ubn_of_birth of animal_migration_table', "\n",
                '12: Replace empty strings by null', "\n",
                '13: Recalculate breedCodes of all offspring of animal by id or uln', "\n",
                '14: Set null boolean values in animal to false for is_departed_animal, is_import_animal, is_export_animal', "\n",
                '15: Remove time from MaediVisna and Scrapie checkdates', "\n",
                self::LINE_THICK, "\n",
                '20: Fix incorrect neuters with ulns matching unassigned tags for given locationId (NOTE! tagsync first!)', "\n\n",
                '================== ANIMAL LOCATION & RESIDENCE ===================', "\n",
                '30: Remove locations and incorrect animal residences for ulns in app/Resources/imports/corrections/remove_locations_by_uln.csv', "\n",
                '31: Kill resurrected dead animals already having a FINISHED or FINISHED_WITH_WARNING last declare loss', "\n",
                '32: Kill alive animals with a date_of_death, even if they don\'t have a declare loss', "\n",
                '33: Fix animal residences: 1. remove duplicates, 2. close open residences by next residence, 3. close open residences by date of death', "\n\n",

                '================== DECLARES ===================', "\n",
                '50: Fill missing messageNumbers in DeclareResponseBases where errorCode = IDR-00015', "\n\n",

                '================== SCAN MEASUREMENTS ===================', "\n",
                '60: Fix duplicate measurements', "\n",
                '61: Create scan measurement set records for unlinked scan measurements', "\n",
                '62: Link latest scan measurement set records to animals', "\n\n",

                'other: exit submenu', "\n"
            ], self::DEFAULT_OPTION);
        } else {
            $option = array_shift($options);
            $this->exitAfterRun = true;
        }

        $ascendantValidator = new AscendantValidator($this->em, $this->cmdUtil, $this->getContainer()->get('logger'));

        switch ($option) {
            case 1: DatabaseDataFixer::updateMaxIdOfAllSequences($this->conn, $this->cmdUtil); break;
            case 2: DatabaseDataFixer::fixGenderTables($this->conn, $this->cmdUtil); break;
            case 3: DatabaseDataFixer::fixIncongruentAnimalOrderNumbers($this->conn, $this->cmdUtil); break;
            case 4: MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false, $this->cmdUtil); break;
            case 5: $this->writeln(LitterUtil::deleteDuplicateLittersWithoutBornAlive($this->conn) . ' litters deleted'); break;
            case 6: $ascendantValidator->run(); break;
            case 7: $ascendantValidator->printOverview(); break;
            case 8: DatabaseDataFixer::recursivelyFillMissingBreedCodesHavingBothParentBreedCodes($this->conn, $this->cmdUtil); break;
            case 9: $this->getContainer()->get('app.migrator.vsm')->getAnimalTableMigrator()->removeNonAlphaNumericSymbolsFromUlnNumberInAnimalTable(); break;
            case 10: $this->getContainer()->get('app.datafix.ubn')->removeNonDigitsFromUbnOfBirthInAnimalTable($this->cmdUtil); break;
            case 11: $this->getContainer()->get('app.datafix.ubn')->removeNonDigitsFromUbnOfBirthInAnimalMigrationTable($this->cmdUtil); break;
            case 12: DatabaseDataFixer::replaceEmptyStringsByNull($this->conn, $this->cmdUtil); break;
            case 13: $this->getContainer()->get('app.datafix.breed_code.offspring.recalculation')->recalculateBreedCodesOfOffspringOfGivenAnimalById($this->cmdUtil); break;
            case 14: DatabaseDataFixer::setAnimalTransferStateNullBooleansAsFalse($this->conn, null, $this->cmdUtil); break;
            case 15: DatabaseDataFixer::removeTimeFromCheckDates($this->conn, $this->cmdUtil); break;

            case 20: DatabaseDataFixer::deleteIncorrectNeutersFromRevokedBirthsWithOptionInput($this->conn, $this->cmdUtil); break;

            case 30: DatabaseDataFixer::removeAnimalsFromLocationAndAnimalResidence($this->conn, $this->cmdUtil); break;
            case 31: DatabaseDataFixer::killResurrectedDeadAnimalsAlreadyHavingFinishedLastDeclareLoss($this->conn, $this->cmdUtil); break;
            case 32: DatabaseDataFixer::killAliveAnimalsWithADateOfDeath($this->conn, $this->cmdUtil); break;
            case 33: DatabaseDataFixer::fixAnimalResidenceRecords($this->conn, $this->getLogger()); break;

            case 50: DatabaseDataFixer::fillBlankMessageNumbersForErrorMessagesWithErrorCodeIDR00015($this->conn, $this->cmdUtil); break;

            case 60: $this->getDuplicateMeasurementsFixer()->deactivateDuplicateMeasurements(); break;
            case 61: MeasurementsUtil::createNewScanMeasurementSetsByUnlinkedData($this->em, $this->getLogger(), $this->getDuplicateMeasurementsFixer()); break;
            case 62: MeasurementsUtil::linkLatestScanMeasurementsToAnimals($this->conn, $this->getLogger()); break;

            default: $this->writeMenuExit(); return;
        }
        if ($this->exitAfterRun) {
            die;
        }
        $this->fixDatabaseValuesOptions();
    }

    private function getDuplicateMeasurementsFixer(): DuplicateMeasurementsFixer
    {
        return $this->getContainer()->get(DuplicateMeasurementsFixer::class);
    }

    public function initializeDatabaseValuesOptions(array $options = [])
    {
        $this->initializeMenu(self::INITIALIZE_DATABASE_VALUES);

        if (empty($options)) {
            $option = $this->cmdUtil->generateMultiLineQuestion([
                'Choose option: ', "\n",
                self::LINE_THICK, "\n",
                '1: BirthProgress', "\n",
                '2: is_rvo_message boolean in action_log', "\n",
                '3: TreatmentType', "\n",
                '4: LedgerCategory', "\n",
                '5: ScrapieGenotypeSource', "\n",
                '6: PedigreeCodes & PedigreeRegister-PedigreeCode relationships', "\n",
                '7: Initialize batch invoice invoice rules', "\n",
                '8: EditType', "\n",
                self::LINE_THICK, "\n",
                '10: StoredProcedures: initialize if not exist', "\n",
                '11: StoredProcedures: overwrite all', "\n",
                '12: SqlViews: initialize if not exist', "\n",
                '13: SqlViews: overwrite all', "\n",
                "\n",

                'other: exit submenu', "\n"
            ], self::DEFAULT_OPTION);
        } else {
            $option = array_shift($options);
            $this->exitAfterRun = true;
        }

        $storedProcedureIntializer = $this->getContainer()->get('AppBundle\Service\Migration\StoredProcedureInitializer');

        switch ($option) {
            case 1: $this->getContainer()->get('app.initializer.birth_progress')->run($this->cmdUtil); break;
            case 2: ActionLogWriter::initializeIsRvoMessageValues($this->conn, $this->cmdUtil); break;
            case 3: $this->getContainer()->get('app.initializer.treatment_type')->run($this->cmdUtil); break;
            case 4: $this->getContainer()->get('AppBundle\Service\Migration\LedgerCategoryMigrator')->run($this->cmdUtil); break;
            case 5:
                $updateCount = $this->em->getRepository(ScrapieGenotypeSource::class)->initializeRecords();
                $this->writeLn(($updateCount ? $updateCount : 'No').' ScrapieGenotypeSources have been inserted');
                break;
            case 6: $this->getContainer()->get('AppBundle\Service\Migration\PedigreeCodeInitializer')->run($this->cmdUtil); break;
            case 7: $this->getContainer()->get('AppBundle\Service\Invoice\BatchInvoiceRuleInitializer')->load(); break;
            case 8: $this->em->getRepository(EditType::class)->initializeRecords(); break;

            case 10: $this->getContainer()->get('AppBundle\Service\Migration\StoredProcedureInitializer')->initialize(); break;
            case 11: $this->getContainer()->get('AppBundle\Service\Migration\StoredProcedureInitializer')->update(); break;

            case 12: $this->getContainer()->get('AppBundle\Service\Migration\SqlViewInitializer')->initialize(); break;
            case 13: $this->getContainer()->get('AppBundle\Service\Migration\SqlViewInitializer')->update(); break;

            default: $this->writeMenuExit(); return;
        }
        if ($this->exitAfterRun) {
            die;
        }

        $this->initializeDatabaseValuesOptions();
    }


    public function fillMissingDataOptions(array $options = [])
    {
        $this->initializeMenu(self::FILL_MISSING_DATA);

        if (empty($options)) {
            $option = $this->cmdUtil->generateMultiLineQuestion([
                'Choose option: ', "\n",
                self::LINE_THICK, "\n",
                '1: Birth Weight and TailLength', "\n",
                '2: UbnOfBirth (string) in Animal', "\n",
                '3: Fill empty breedCode, breedType and pedigree (stn) data for all declareBirth animals (no data is overwritten)', "\n",
                '4: Fill empty scrapieGenotype data for all declareBirth animals currently on livestocks (no data is overwritten)', "\n",
                '5: Fill missing pedigreeRegisterIds by breedNumber in STN (no data is overwritten)', "\n",
                self::LINE_THIN, "\n",
                '6: Inbreeding coefficients, generate if empty, all', "\n",
                '7: Inbreeding coefficients, generate if empty, for given ubn', "\n",
                '8: Inbreeding coefficients, regenerate if empty, for given ubn', "\n",
                "\n",
                'other: exit submenu', "\n"
            ], self::DEFAULT_OPTION);
        } else {
            $option = array_shift($options);
            $this->exitAfterRun = true;
        }

        switch ($option) {
            case 1: $this->getContainer()->get('app.datafix.birth.measurements.missing')->run(); break;
            case 2: $this->getContainer()->get('AppBundle\Service\DataFix\MissingUbnOfBirthFillerService')->run(); break;
            case 3: $this->getContainer()->get('AppBundle\Service\Migration\PedigreeDataReprocessor')->run($this->cmdUtil); break;
            case 4: $this->getContainer()->get('AppBundle\Service\Migration\ScrapieGenotypeReprocessor')->run($this->cmdUtil); break;
            case 5: $this->getContainer()->get('AppBundle\Service\Migration\PedigreeDataReprocessor')->batchMatchMissingPedigreeRegisterByBreederNumberInStn(); break;
            case 6: $this->getContainer()->get(InbreedingCoefficientUpdaterService::class)->generateForAllAnimalsAndLitters(); break;
            case 7: $this->getContainer()->get(InbreedingCoefficientUpdaterService::class)->generateForAnimalsAndLittersOfUbn($this->askForUbn()); break;
            case 8: $this->getContainer()->get(InbreedingCoefficientUpdaterService::class)->regenerateForAnimalsAndLittersOfUbn($this->askForUbn()); break;

            default: $this->writeMenuExit(); return;
        }
        if ($this->exitAfterRun) {
            die;
        }
        $this->fillMissingDataOptions();
    }


    public function dataMigrationOptions()
    {
        $this->initializeMenu(CommandTitle::DATA_MIGRATION);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            self::LINE_THICK, "\n",
            '1: '.strtolower(CommandTitle::INSPECTOR), "\n",
            '2: '.strtolower(CommandTitle::DATA_MIGRATE_2017_AND_WORM), "\n",
            '3: '.strtolower(CommandTitle::PEDIGREE_REGISTER_REGISTRATION), "\n",
            '4: '.strtolower(CommandTitle::SCAN_MEASUREMENTS_DATA), "\n",
            "\n",
            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->getContainer()->get('app.migrator.inspector')->run($this->cmdUtil); break;
            case 2: $this->getContainer()->get('app.migrator.vsm')->run($this->cmdUtil); break;
            case 3: $this->getContainer()->get('AppBundle\Service\Migration\PedigreeRegisterRegistrationMigrator')->run($this->cmdUtil); break;
            case 4: $this->getContainer()->get('AppBundle\Service\Migration\ScanMeasurementsMigrator')->run($this->cmdUtil); break;
            default: $this->writeMenuExit(); return;
        }
        $this->dataMigrationOptions();
    }



    /**
     * @param CommandUtil $cmdUtil
     */
    public function runMixblupCliOptions(CommandUtil $cmdUtil)
    {
        if ($this->cmdUtil === null) { $this->cmdUtil = $cmdUtil; }

        //Print intro
        $cmdUtil->writelnClean(CommandUtil::generateTitle(CommandTitle::MIXBLUP));
        $cmdUtil->writelnClean([DoctrineUtil::getDatabaseHostAndNameString($this->em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate & Upload MixBlupInputFiles and send message to MixBlup queue', "\n",
            '2: Download and process MixBlup output files (relani & solani)', "\n",
            '3: Generate MixBlup instruction files only', "\n",
            '4: Initialize blank genetic bases', "\n",
            '5: Set minimum reliability for all breedValueTypes by accuracy option', "\n",
            '6: Update/Insert LambMeatIndex values by generationDate (excl. resultTable update)', "\n",
            '7: Update breedIndex & breedValue normal distribution values', "\n",
            '8: Deactivate breedValueResultTable processor logs', "\n",
            self::LINE_THICK, "\n",
            '10: Initialize BreedIndexType and BreedValueType', "\n",
            '11: Initialize MixBlupAnalysisTypes', "\n",
            '12: Delete all duplicate breedValues', "\n",
            self::LINE_THICK, "\n",
            '13: Update result_table_breed_grades values and accuracies for all breedValue and breedIndex types (including prerequisite options)', "\n",
            '14: Update result_table_breed_grades values and accuracies for '.MixBlupType::LAMB_MEAT_INDEX.self::TYPES_EXCLUDING_PREREQUISITES, "\n",
            '15: Update result_table_breed_grades values and accuracies for '.MixBlupType::FERTILITY.self::TYPES_EXCLUDING_PREREQUISITES, "\n",
            '16: Update result_table_breed_grades values and accuracies for '.MixBlupType::WORM.self::TYPES_EXCLUDING_PREREQUISITES, "\n",
            '17: Update result_table_breed_grades values and accuracies for '.MixBlupType::EXTERIOR.self::TYPES_EXCLUDING_PREREQUISITES, "\n",
            self::LINE_THICK, "\n",
            '18: Initialize lambMeatIndexCoefficients', "\n",
            '19: Initialize wormResistanceIndexCoefficients', "\n",
            self::LINE_THICK, "\n",
            '20: Validate ubnOfBirth format as !BLOCK in DataVruchtb.txt in mixblup cache folder', "\n",
            '21: Validate ubnOfBirth format as !BLOCK in PedVruchtb.txt in mixblup cache folder', "\n",
            self::LINE_THICK, "\n",
            '30: Print separate csv files of latest breedValues for all ubns', "\n",
            '31: Print separate csv files of latest breedValues for chosen ubn', "\n",
            self::LINE_THICK, "\n",
            '40: Clear excel cache folder', "\n",
            '41: Print CSV file for CF pedigree register', "\n",
            '42: Print CSV file for NTS, TSNH, LAX pedigree registers', "\n",
            '43: Print CSV file Breedvalues overview all animals on a ubn, with atleast one breedValue', "\n",
            '44: Print CSV file Breedvalues overview all animals on a ubn, even those without a breedValue', "\n",
            self::LINE_THICK, "\n",
            '50: Generate & Upload EXTERIOR MixBlupInputFiles and send message to MixBlup queue', "\n",
            '51: Generate & Upload LAMB MEAT INDEX MixBlupInputFiles and send message to MixBlup queue', "\n",
            '52: Generate & Upload FERTILITY MixBlupInputFiles and send message to MixBlup queue', "\n",
            '53: Generate & Upload WORM MixBlupInputFiles and send message to MixBlup queue', "\n",
            'other: EXIT ', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1: $this->getMixBlupInputFilesService()->run(); break;
            case 2: $this->getMixBlupOutputFilesService()->run(); break;
            case 3: $this->getMixBlupInputFilesService()->writeInstructionFiles(); break;
            case 4: $this->getBreedValueService()->initializeBlankGeneticBases(); break;
            case 5: $this->getBreedValueService()->setMinReliabilityForAllBreedValueTypesByAccuracyOption($this->cmdUtil); break;
            case 6: $this->updateLambMeatIndexesByGenerationDate(); break;
            case 7: $this->updateBreedIndexAndBreedValueNormalDistributions(); break;
            case 8: $this->deactivateBreedValuesResultTableUpdaterLogs(); break;


            case 10:
                $this->getBreedIndexService()->initializeBreedIndexType();
                $this->getBreedValueService()->initializeBreedValueType();
                $this->getBreedValueService()->initializeCustomBreedValueTypeSettings();
                break;
            case 11:
                $this->getMixBlupAnalysisTypeMigrator()->run($this->cmdUtil);
                break;
            case 12:
                $deleteCount = MixBlupOutputFilesService::deleteDuplicateBreedValues($this->conn);
                $message = $deleteCount > 0 ? $deleteCount . ' duplicate breedValues were deleted' : 'No duplicate breedValues found';
                $this->cmdUtil->writeln($message);
                break;

            case 13: $this->updateAllResultTableValuesAndPrerequisites(); break;
            case 14: $this->getBreedValuesResultTableUpdater()->update([MixBlupType::LAMB_MEAT_INDEX],
                $this->insertMissingResultTableAndGeneticBaseRecords(), $this->ignorePreviouslyFinishedProcesses()); break;
            case 15: $this->getBreedValuesResultTableUpdater()->update([MixBlupType::FERTILITY],
                $this->insertMissingResultTableAndGeneticBaseRecords(), $this->ignorePreviouslyFinishedProcesses()); break;
            case 16: $this->getBreedValuesResultTableUpdater()->update([MixBlupType::WORM],
                $this->insertMissingResultTableAndGeneticBaseRecords(), $this->ignorePreviouslyFinishedProcesses()); break;
            case 17: $this->getBreedValuesResultTableUpdater()->update([MixBlupType::EXTERIOR],
                $this->insertMissingResultTableAndGeneticBaseRecords(), $this->ignorePreviouslyFinishedProcesses()); break;

            case 18: $this->getLambMeatIndexMigrator()->migrate(); break;
            case 19: $this->getWormResistanceIndexMigrator()->migrate(); break;

            case 20: $this->getMixBlupInputFileValidator()->validateUbnOfBirthInDataFile($this->cmdUtil); break;
            case 21: $this->getMixBlupInputFileValidator()->validateUbnOfBirthInPedigreeFile($this->cmdUtil); break;

            case 30: $this->printBreedValuesAllUbns(); break;
            case 31: $this->printBreedValuesByUbn(); break;

            case 40: $this->getExcelService()->clearCacheFolder(); break;
            case 41:
                $filepath = $this->getPedigreeRegisterOverviewReportService()->generateFileByType(PedigreeAbbreviation::CF, false, FileType::CSV);
                $this->getLogger()->notice($filepath);
                break;
            case 42:
                $filepath = $this->getPedigreeRegisterOverviewReportService()->generateFileByType(PedigreeAbbreviation::NTS,false, FileType::CSV);
                $this->getLogger()->notice($filepath);
                break;
            case 43: $filepath = $this->getBreedValuesOverviewReportService()->generate(FileType::CSV, false, false, false);
                $this->getLogger()->notice($filepath);
                break;
            case 44: $filepath = $this->getBreedValuesOverviewReportService()->generate(FileType::CSV, false, true, false);
                $this->getLogger()->notice($filepath);
                break;

            case 50: $this->getMixBlupInputFilesService()->runExterior(); break;
            case 51: $this->getMixBlupInputFilesService()->runLambMeatIndex(); break;
            case 52: $this->getMixBlupInputFilesService()->runFertility(); break;
            case 53: $this->getMixBlupInputFilesService()->runWorm(); break;

            default: return;
        }
    }


    private function ignorePreviouslyFinishedProcesses(): bool {
        $ignorePreviouslyFinishedProcesses = $this->cmdUtil->generateConfirmationQuestion('Ignore previously finished processes? (y/n, default is false)', false);
        $this->cmdUtil->writeln('Ignore previously finished processes: '. StringUtil::getBooleanAsString($ignorePreviouslyFinishedProcesses));
        return $ignorePreviouslyFinishedProcesses;
    }


    private function insertMissingResultTableAndGeneticBaseRecords(): bool {
        $question = 'Insert missing resultTable and genetic base records';
        $choice = $this->cmdUtil->generateConfirmationQuestion($question.'? (y/n, default is true)', true);
        $this->cmdUtil->writeln($question.': '. StringUtil::getBooleanAsString($choice));
        return $choice;
    }


    private function deactivateBreedValuesResultTableUpdaterLogs() {
        $updateCount = $this->em->getRepository(ProcessLog::class)->deactivateBreedValuesResultTableUpdaterProcessLog();
        $this->cmdUtil->writeln($updateCount . ' breedValuesResultTableUpdaterProcessLogs deactivated');
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    private function updateAllResultTableValuesAndPrerequisites()
    {
        /*
         * Options
         */
        $ignoreAllPrerequisiteChecks = $this->cmdUtil->generateConfirmationQuestion('Ignore all prerequisite checks? (y/n, default is false)');
        $this->getLogger()->notice('Ignore all prerequisite checks: '. StringUtil::getBooleanAsString($ignoreAllPrerequisiteChecks));

        if ($ignoreAllPrerequisiteChecks) {
            $updateBreedIndexes = false;
            $updateNormalDistributions = false;
            $insertMissingResultTableRecords = false;
        } else {
            $updateBreedIndexes = $this->cmdUtil->generateConfirmationQuestion('Update BreedIndexes? (y/n, default is false)');
            $this->getLogger()->notice('Update BreedIndexes: '. StringUtil::getBooleanAsString($updateBreedIndexes));

            $updateNormalDistributions = $this->cmdUtil->generateConfirmationQuestion('Update NormalDistributions? (y/n, default is false)');
            $this->getLogger()->notice('Update NormalDistributions: '. StringUtil::getBooleanAsString($updateNormalDistributions));

            $insertMissingResultTableRecords = $this->insertMissingResultTableAndGeneticBaseRecords();
        }

        $ignorePreviouslyFinishedProcesses = $this->ignorePreviouslyFinishedProcesses();

        $useLastGenerationDateString = 'Use last generationDateString for all breedValueTypes';
        $useLastGenerationDateStringChoice = $this->cmdUtil->generateQuestion($useLastGenerationDateString.' (default: true)', true);

        if ($useLastGenerationDateStringChoice) {
            $generationDateString = $this->getBreedValuesResultTableUpdater()->getGenerationDateString();
        } else {
            $generationDateString = $this->cmdUtil->generateQuestion('Insert custom GenerationDateString (default: The generationDateString of the last inserted breedValue will be used)', null);
        }

        // End of options

        $this->getBreedValuesResultTableUpdater()->update(
            [],
            $insertMissingResultTableRecords,
            $ignorePreviouslyFinishedProcesses,
            $updateBreedIndexes,
            $updateNormalDistributions,
            $generationDateString
        );
    }

    private function askForUbn(): string {
        return $this->cmdUtil->generateQuestion('insert ubn (default: '.self::DEFAULT_UBN.')', self::DEFAULT_UBN);
    }

    private function printBreedValuesAllUbns()
    {
        do {
            $ubn = $this->cmdUtil->generateQuestion('insert minimum ubn (default: '.self::DEFAULT_MIN_UBN.')', self::DEFAULT_MIN_UBN);
        } while(!ctype_digit($ubn) && !is_int($ubn));
        $this->cmdUtil->writeln('Generating breedValues csv file with minimum UBN of: '.$ubn.' ...');
        $this->getBreedValuePrinter()->printBreedValuesAllUbns($ubn);
        $this->cmdUtil->writeln('Generated breedValues csv file with minimum UBN of: '.$ubn.' ...');
    }


    private function printBreedValuesByUbn()
    {
        do {
            $ubn = $this->askForUbn();
        } while(!ctype_digit($ubn) && !is_int($ubn));
        $this->cmdUtil->writeln('Generating breedValues csv file for UBN: '.$ubn.' ...');
        $this->getBreedValuePrinter()->printBreedValuesByUbn($ubn);
        $this->cmdUtil->writeln('BreedValues csv file generated for UBN: '.$ubn);
    }


    private function updateLambMeatIndexesByGenerationDate()
    {
        do {
            $generationDateString = $this->cmdUtil->generateQuestion('insert generationDate string in following format: 2017-01-01 00:00:00 (default: '.self::DEFAULT_GENERATION_DATE_STRING.')', self::DEFAULT_GENERATION_DATE_STRING, false);
        } while(!TimeUtil::isValidDateTime($generationDateString));

        $this->getBreedIndexService()->updateLambMeatIndexes($generationDateString);
    }


    private function updateBreedIndexAndBreedValueNormalDistributions()
    {
        /*
         * Options
         */
        $updateBreedIndexNormalDistributions = $this->cmdUtil->generateConfirmationQuestion('Update BreedIndex normal distributions?', true, true);

        $updateBreedValueNormalDistributions = $this->cmdUtil->generateConfirmationQuestion('Update BreedValue normal distributions?', true, true);

        $overwriteExistingValues = $this->cmdUtil->generateConfirmationQuestion('OVERWRITE EXISTING VALUES?', true, true);

        // End of options

        if ($updateBreedIndexNormalDistributions) {
            $this->getContainer()->get(BreedValuesResultTableUpdater::class)->updateAllBreedIndexNormalDistributions($overwriteExistingValues);
        }

        if ($updateBreedValueNormalDistributions) {
            $this->getContainer()->get(BreedValuesResultTableUpdater::class)->updateAllBreedValueNormalDistributions($overwriteExistingValues);
        }

    }



    public function calculationsAndAlgorithmsOptions(array $options = [])
    {
        $this->initializeMenu(CommandTitle::CALCULATIONS_AND_ALGORITHMS);

        if (empty($options)) {
            $option = $this->cmdUtil->generateMultiLineQuestion([
                'Choose option: ', "\n",
                '1: Validate UBN, all types', "\n",
                '2: Validate UBN, Dutch format only', "\n",
                '3: Validate UBN, non-Dutch format only', "\n",
                '4: Validate UBN format of all active locations', "\n",
                "\n",
                'other: exit submenu', "\n"
            ], self::DEFAULT_OPTION);
        } else {
            $option = array_shift($options);
            $this->exitAfterRun = true;
        }

        switch ($option) {

            case 1: $this->validateUbn(1); break;
            case 2: $this->validateUbn(2); break;
            case 3: $this->validateUbn(3); break;
            case 4: $this->validataUbnsOfAllActiveLocations(); break;
            default: $this->writeMenuExit(); return;
        }
        if ($this->exitAfterRun) {
            die;
        }
        $this->calculationsAndAlgorithmsOptions();
    }


    private function validateUbn(int $option)
    {

        do {
            $ubn = $this->cmdUtil->generateQuestion('insert ubn (default: '.self::DEFAULT_UBN.')', self::DEFAULT_UBN);

            switch ($option) {
                case 1: $isUbnValid = Validator::hasValidUbnFormat($ubn); break;
                case 2: $isUbnValid = Validator::hasValidDutchUbnFormat($ubn); break;
                case 3: $isUbnValid = Validator::hasValidNonNlUbnFormat($ubn); break;
                default: $isUbnValid = Validator::hasValidUbnFormat($ubn); break;
            }

            $this->writeLn('UBN '.strval($ubn).' is '.($isUbnValid ? 'VALID' : 'NOT VALID'));

            $retry = $this->cmdUtil->generateConfirmationQuestion('Test another UBN?', true, false);
        } while($retry);
    }


    private function validataUbnsOfAllActiveLocations()
    {
        $nlCountryCode = Country::NL;
        $sql = "SELECT
                  l.ubn,
                  country.code,
                  country.code = '$nlCountryCode' as is_dutch_location
                FROM location l
                INNER JOIN company c ON c.id = l.company_id
                INNER JOIN address a ON l.address_id = a.id
                INNER JOIN country ON country.id = a.country_details_id
                WHERE l.is_active AND c.is_active";
        $sqlResults = $this->conn->query($sql)->fetchAll();

        $invalidUbns = [];
        foreach ($sqlResults as $sqlResult) {
            $ubn = $sqlResult['ubn'];
            $isDutchLocation = $sqlResult['is_dutch_location'];
            $isValid = Validator::hasValidUbnFormatByLocationType($ubn, $isDutchLocation);
            if (!$isValid) {
                $invalidUbns[$ubn] = $sqlResult['code'];
            }
        }

        if (empty($invalidUbns)) {
            $this->writeLn('All ACTIVE UBNs have a valid format!');
        } else {
            $this->writeLn(count($invalidUbns).' INVALID ACTIVE UBNS FOUND!');
            $this->writeLn($invalidUbns);
        }
    }


    public function processLockerOptions(array $options = [])
    {
        $this->initializeMenu(CommandTitle::PROCESS_LOCKER);

        if (empty($options)) {
            $option = $this->cmdUtil->generateMultiLineQuestion([
                'Choose option: ', "\n",
                '1: Display all processes', "\n",
                MainCommandUtil::UNLOCK_ALL_PROCESSES . ': Unlock  all processes', "\n",
                '3: Display feedback worker processes', "\n",
                '4: Unlock  feedback worker processes', "\n",
                "\n",
                'other: exit submenu', "\n"
            ], self::DEFAULT_OPTION);
        } else {
            $option = array_shift($options);
            $this->exitAfterRun = true;
        }

        switch ($option) {
            case 1: $this->displayAllLockedProcesses(); break;
            case MainCommandUtil::UNLOCK_ALL_PROCESSES: $this->unlockAllProcesses(); break;
            case 3: $this->displayLockedProcesses(ProcessType::SQS_FEEDBACK_WORKER); break;
            case 4: $this->unlockWorkerProcesses(ProcessType::SQS_FEEDBACK_WORKER); break;
            default: $this->writeMenuExit(); return;
        }
        if ($this->exitAfterRun) {
            die;
        }
        $this->processLockerOptions();
    }

    private function displayAllLockedProcesses()
    {
        foreach (ProcessType::getConstants() as $processType) {
            $this->displayLockedProcesses($processType);
        }
    }

    private function displayLockedProcesses($processType)
    {
        $this->getProcessLocker()->initializeProcessGroupValues($processType);
        $this->getProcessLocker()->getProcessesCount($processType, true);
    }

    private function unlockAllProcesses()
    {
        foreach (ProcessType::getConstants() as $processType) {
            $this->unlockWorkerProcesses($processType);
        }
    }

    private function unlockWorkerProcesses($processType)
    {
        $this->getProcessLocker()->initializeProcessGroupValues($processType);
        $this->getProcessLocker()->getProcessesCount($processType, true);
        $this->getProcessLocker()->removeAllProcessesOfGroup($processType);
    }


    public function redisOptions()
    {
        $this->initializeMenu(CommandTitle::REDIS);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Clear all', "\n",
            '2: Clear by location primary key', "\n",
            "\n",
            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->getCacheService()->clear(); break;
            case 2: $this->clearRedisCacheByLocation(); break;
            default: $this->writeMenuExit(); return;
        }
        $this->redisOptions();
    }


    private function clearRedisCacheByLocation()
    {
        do {
            $locationId = $this->cmdUtil->questionForIntChoice(262,'location primary key');
            $location = $this->em->getRepository(Location::class)->find($locationId);

        } while (empty($location));

        $this->writeLn("Clearing redis cache for location ".$locationId.", UBN ".$location->getUbn());

        $this->getCacheService()->clearLivestockCacheForLocation($location, null);
    }


    private function writeLn($line)
    {
        $this->cmdUtil->writelnWithTimestamp($line);
    }


    private function printDbInfo()
    {
        $this->cmdUtil->writeln(DoctrineUtil::getDatabaseHostAndNameString($this->em));
        $this->cmdUtil->writeln('');
    }


    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->getContainer()->get('logger');
    }

    /**
     * @return BreedValuesOverviewReportService
     */
    public function getBreedValuesOverviewReportService()
    {
        return $this->getContainer()->get('AppBundle\Service\Report\BreedValuesOverviewReportService');
    }

    /**
     * @return BreedValuePrinter
     */
    public function getBreedValuePrinter()
    {
        return $this->getContainer()->get('AppBundle\Service\BreedValuePrinter');
    }

    /**
     * @return BreedValueService
     */
    public function getBreedValueService()
    {
        return $this->getContainer()->get('AppBundle\Service\BreedValueService');
    }

    /**
     * @return BreedIndexService
     */
    public function getBreedIndexService()
    {
        return $this->getContainer()->get('AppBundle\Service\BreedIndexService');
    }

    /**
     * @return ExcelService
     */
    public function getExcelService()
    {
        return $this->getContainer()->get('AppBundle\Service\ExcelService');
    }

    /**
     * @return LambMeatIndexMigrator
     */
    public function getLambMeatIndexMigrator()
    {
        return $this->getContainer()->get('AppBundle\Service\Migration\LambMeatIndexMigrator');
    }

    /**
     * @return WormResistanceIndexMigrator
     */
    public function getWormResistanceIndexMigrator()
    {
        return $this->getContainer()->get('AppBundle\Service\Migration\WormResistanceIndexMigrator');
    }

    /**
     * @return MixBlupInputFilesService
     */
    public function getMixBlupInputFilesService()
    {
        return $this->getContainer()->get('AppBundle\Service\MixBlupInputFilesService');
    }

    /**
     * @return MixBlupInputFileValidator
     */
    public function getMixBlupInputFileValidator()
    {
        return $this->getContainer()->get('AppBundle\Component\MixBlup\MixBlupInputFileValidator');
    }

    /**
     * @return MixBlupOutputFilesService
     */
    public function getMixBlupOutputFilesService()
    {
        return $this->getContainer()->get('AppBundle\Service\MixBlupOutputFilesService');
    }

    /**
     * @return PedigreeRegisterOverviewReportService
     */
    public function getPedigreeRegisterOverviewReportService()
    {
        return $this->getContainer()->get('AppBundle\Service\Report\PedigreeRegisterOverviewReportService');
    }

    /**
     * @return MixBlupAnalysisTypeMigrator
     */
    public function getMixBlupAnalysisTypeMigrator()
    {
        return $this->getContainer()->get('AppBundle\Service\Migration\MixBlupAnalysisTypeMigrator');
    }

    /**
     * @return BreedValuesResultTableUpdater
     */
    public function getBreedValuesResultTableUpdater()
    {
        return $this->getContainer()->get('AppBundle\Cache\BreedValuesResultTableUpdater');
    }

    /**
     * @return ProcessLockerInterface
     */
    public function getProcessLocker()
    {
        return $this->getContainer()->get('AppBundle\Service\ProcessLocker');
    }

    /**
     * @return CacheService
     */
    public function getCacheService()
    {
        return $this->getContainer()->get('app.cache');
    }
}
