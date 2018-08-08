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
use AppBundle\Component\MixBlup\MixBlupInputFileValidator;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\EditType;
use AppBundle\Entity\ScrapieGenotypeSource;
use AppBundle\Entity\TagSyncErrorLog;
use AppBundle\Entity\TagSyncErrorLogRepository;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Enumerator\PedigreeAbbreviation;
use AppBundle\Service\BreedIndexService;
use AppBundle\Service\BreedValuePrinter;
use AppBundle\Service\BreedValueService;
use AppBundle\Service\ExcelService;
use AppBundle\Service\Migration\LambMeatIndexMigrator;
use AppBundle\Service\Migration\MixBlupAnalysisTypeMigrator;
use AppBundle\Service\Migration\WormResistanceIndexMigrator;
use AppBundle\Service\MixBlupInputFilesService;
use AppBundle\Service\MixBlupOutputFilesService;
use AppBundle\Service\Report\BreedValuesOverviewReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DatabaseDataFixer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\ErrorLogUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\AscendantValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
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


    /** @var ObjectManager|EntityManagerInterface */
    private $em;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var Connection */
    private $conn;

    protected function configure()
    {
        $this
            ->setName('nsfo')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cmdUtil = new CommandUtil($input, $output, $this->getHelper('question'));

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->conn = $this->em->getConnection();

        $this->mainMenu();
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
        $this->cmdUtil->writeln("
                                            III III                                     
                                            IIIIIII                                     
                                             IIIII                                      
                                              III                                       
                                    ,,,,,             88888                             
                                  ,,,,,,,DDD       DDZ8888888                           
                                DDD,88888             :::::8DDD                         
              ,,,,,       ,,,,,,,,,,,88888           :::::88888888888$    0888888$      
          ,,,,,,,,,,,,,,,,,,,,,,,,,,,,88888         :::::8888888888888888888888888888   
         ,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,             88888888888888888888888888888888  
        ,,,,,,,,,,,,,,,,,,,,,,,,,,,,:,,,               88888888888888888888888888888888 
        ,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,                 8888888888888888888888888888888 
        ,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,                 8888888888888888888888888888888 
         ,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,                 888888888888888888888888888888  
         ,,,,,,,,,,,,,,,,,,,,,,,,,,,,,                   88888888888888888888888888888  
         :,,,,,,,,,,,,,,,,, ,,,,,,,,,                     888888888D88888888888888888D  
         ::,,,,,, ,,,,,,,,, ,,,,,::                         DD88888 888888888 888888DD  
        :::,,,,              ,,,:=                           DD888              8888DDD 
        :::,,                8 88                             :: :                88DDD 
         ::,,                8 8                              :: :               888DD  
         :: ,8              8 Z8                               : ::              :8 DD  
          8  8I             88 8                               : ::             ::  :   
                                                              == =              =   =   ");
    }


    /**
     * @param bool $isIntroScreen
     */
    public function mainMenu($isIntroScreen = true)
    {
        if ($isIntroScreen) {
            $this->printAsciiArt();
        }

        $this->initializeMenu(self::MAIN_TITLE);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            '===============================================', "\n",
            'SELECT SUBMENU: ', "\n",
            '===============================================', "\n",
            '1: '.strtolower(self::INFO_SYSTEM_SETTINGS), "\n",
            '-----------------------------------------------', "\n",
            '2: '.strtolower(self::ANIMAL_CACHE_TITLE), "\n",
            '3: '.strtolower(self::LITTER_GENE_DIVERSITY_TITLE), "\n",
            '4: '.strtolower(self::ERROR_LOG_TITLE), "\n",
            '5: '.strtolower(self::FIX_DUPLICATE_ANIMALS), "\n",
            '6: '.strtolower(self::FIX_DATABASE_VALUES), "\n",
            '7: '.strtolower(self::GENDER_CHANGE), "\n",
            '8: '.strtolower(self::INITIALIZE_DATABASE_VALUES), "\n",
            '9: '.strtolower(self::FILL_MISSING_DATA), "\n",
            '-----------------------------------------------', "\n",
            '10: '.strtolower(CommandTitle::DATA_MIGRATION), "\n",
            '-----------------------------------------------', "\n",
            '11: '.strtolower(CommandTitle::MIXBLUP), "\n",
            '-----------------------------------------------', "\n",
            '12: '.strtolower(CommandTitle::DEPART_INTERNAL_WORKER), "\n",
            '===============================================', "\n",
            'other: EXIT ', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->getContainer()->get('app.info.parameters')->printInfo(); break;

            case 2: $this->animalCacheOptions(); break;
            case 3: $this->litterAndGeneDiversityOptions(); break;
            case 4: $this->errorLogOptions(); break;
            case 5: $this->fixDuplicateAnimalsOptions(); break;
            case 6: $this->fixDatabaseValuesOptions(); break;
            case 7: $this->getContainer()->get('app.cli.gender_changer')->run($this->cmdUtil); break;
            case 8: $this->initializeDatabaseValuesOptions(); break;
            case 9: $this->fillMissingDataOptions(); break;
            case 10: $this->dataMigrationOptions(); break;
            case 11: $this->runMixblupCliOptions($this->cmdUtil); break;
            case 12: $this->getContainer()->get('app.cli.internal_worker.depart')->run($this->cmdUtil); break;

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
            '-------------------------', "\n",
            '24: BatchInsert empty animal_cache records and BatchUpdate all Incongruent values', "\n\n",
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

            case 99:
                $this->printLocationIdFromGivenUbn();
                $this->cmdUtil->writeln('DONE!');
                break;

            default: $this->writeLn('Exit menu'); return;
        }
        $this->animalCacheOptions();
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
            '11: BatchUpdate gestationPeriods in Litters, update all incongruous values (incl. revoked litters and mates)', "\n",
            '12: BatchUpdate birthIntervals in Litters, update all incongruous values (incl. revoked litters and mates NOTE! Update litterOrdinals first!)', "\n\n",

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
            case 11: $this->writeLn(LitterUtil::updateGestationPeriods($this->conn).' gestationPeriods updated'); break;
            case 12: $this->writeLn(LitterUtil::updateBirthInterVal($this->conn).' birthIntervals updated'); break;

            default: $this->writeLn('Exit menu'); return;
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

            default: $this->writeLn('Exit menu'); return;
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
            '6: Merge two animals by uln (primary) and pedigreeNumber (secondary) csv correction file', "\n",
            '7: Merge two animals by ulns in correction file and create tag replace', "\n\n",
            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        $duplicateAnimalsFixer = $this->getContainer()->get('app.datafix.animals.duplicate');

        switch ($option) {
            case 1: $duplicateAnimalsFixer->fixDuplicateAnimalsGroupedOnUlnVsmIdDateOfBirth($this->cmdUtil); break;
            case 2: $duplicateAnimalsFixer->fixDuplicateAnimalsSyncedAndImportedPairs($this->cmdUtil); break;
            case 3: $duplicateAnimalsFixer->mergeAnimalPairs($this->cmdUtil); break;
            case 4: $duplicateAnimalsFixer->mergeImportedAnimalsMissingLeadingZeroes($this->cmdUtil); break;
            case 5: $duplicateAnimalsFixer->fixDuplicateDueToTagReplaceError($this->cmdUtil); break;
            case 6: $duplicateAnimalsFixer->mergePrimaryUlnWithSecondaryPedigreeNumberFromCsvFile($this->cmdUtil); break;
            case 7: $duplicateAnimalsFixer->mergeByUlnStringsAndCreateDeclareTagReplace($this->cmdUtil); break;
            default: $this->writeLn('Exit menu'); return;
        }
        $this->fixDuplicateAnimalsOptions();
    }


    public function fixDatabaseValuesOptions()
    {
        $this->initializeMenu(self::FIX_DATABASE_VALUES);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '=====================================', "\n",
            '1: Update MaxId of all sequences', "\n",
            '=====================================', "\n",
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
            '=====================================', "\n",
            '20: Fix incorrect neuters with ulns matching unassigned tags for given locationId (NOTE! tagsync first!)', "\n\n",
            '================== ANIMAL LOCATION & RESIDENCE ===================', "\n",
            '30: Remove locations and incorrect animal residences for ulns in app/Resources/imports/corrections/remove_locations_by_uln.csv', "\n",
            '31: Kill resurrected dead animals already having a FINISHED or FINISHED_WITH_WARNING last declare loss', "\n",
            '32: Kill alive animals with a date_of_death, even if they don\'t have a declare loss', "\n",
            '33: Remove duplicate animal residences with endDate isNull', "\n\n",

            '================== DECLARES ===================', "\n",
            '50: Fill missing messageNumbers in DeclareReponseBases where errorCode = IDR-00015', "\n\n",
            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

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
            case 33: DatabaseDataFixer::removeDuplicateAnimalResidencesWithEndDateIsNull($this->conn, $this->cmdUtil); break;

            case 50: DatabaseDataFixer::fillBlankMessageNumbersForErrorMessagesWithErrorCodeIDR00015($this->conn, $this->cmdUtil); break;

            default: $this->writeLn('Exit menu'); return;
        }
        $this->fixDatabaseValuesOptions();
    }


    public function initializeDatabaseValuesOptions()
    {
        $this->initializeMenu(self::INITIALIZE_DATABASE_VALUES);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '=====================================', "\n",
            '1: BirthProgress', "\n",
            '2: is_rvo_message boolean in action_log', "\n",
            '3: TreatmentType', "\n",
            '4: LedgerCategory', "\n",
            '5: ScrapieGenotypeSource', "\n",
            '6: PedigreeCodes & PedigreeRegister-PedigreeCode relationships', "\n",
            '7: Initialize batch invoice invoice rules', "\n",
            '8: EditType', "\n",
            '=====================================', "\n",
            '10: StoredProcedures: initialize if not exist', "\n",
            '11: StoredProcedures: overwrite all', "\n",
            '12: SqlViews: initialize if not exist', "\n",
            '13: SqlViews: overwrite all', "\n",
            "\n",

            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

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

            default: $this->writeLn('Exit menu'); return;
        }
        $this->initializeDatabaseValuesOptions();
    }


    public function fillMissingDataOptions()
    {
        $this->initializeMenu(self::FILL_MISSING_DATA);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '=====================================', "\n",
            '1: Birth Weight and TailLength', "\n",
            '2: UbnOfBirth (string) in Animal', "\n",
            '3: Fill empty breedCode, breedType and pedigree (stn) data for all declareBirth animals (no data is overwritten)', "\n",
            '4: Fill empty scrapieGenotype data for all declareBirth animals currently on livestocks (no data is overwritten)', "\n",
            '5: Fill missing pedigreeRegisterIds by breedNumber in STN (no data is overwritten)', "\n",
            "\n",
            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->getContainer()->get('app.datafix.birth.measurements.missing')->run(); break;
            case 2: $this->getContainer()->get('AppBundle\Service\DataFix\MissingUbnOfBirthFillerService')->run(); break;
            case 3: $this->getContainer()->get('AppBundle\Service\Migration\PedigreeDataReprocessor')->run($this->cmdUtil); break;
            case 4: $this->getContainer()->get('AppBundle\Service\Migration\ScrapieGenotypeReprocessor')->run($this->cmdUtil); break;
            case 5: $this->getContainer()->get('AppBundle\Service\Migration\PedigreeDataReprocessor')->batchMatchMissingPedigreeRegisterByBreederNumberInStn(); break;

            default: $this->writeLn('Exit menu'); return;
        }
        $this->fillMissingDataOptions();
    }


    public function dataMigrationOptions()
    {
        $this->initializeMenu(CommandTitle::DATA_MIGRATION);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '=====================================', "\n",
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
            default: $this->writeLn('Exit menu'); return;
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
            '========================================================================', "\n",
            '10: Initialize BreedIndexType and BreedValueType', "\n",
            '11: Initialize MixBlupAnalysisTypes', "\n",
            '12: Delete all duplicate breedValues', "\n",
            '========================================================================', "\n",
            '13: Update result_table_breed_grades values and accuracies for all breedValue and breedIndex types (including prerequisite options)', "\n",
            '14: Update result_table_breed_grades values and accuracies for '.MixBlupType::LAMB_MEAT_INDEX.' types (excluding prerequisites)', "\n",
            '15: Update result_table_breed_grades values and accuracies for '.MixBlupType::FERTILITY.' types (excluding prerequisites)', "\n",
            '16: Update result_table_breed_grades values and accuracies for '.MixBlupType::WORM.' types (excluding prerequisites)', "\n",
            '17: Update result_table_breed_grades values and accuracies for '.MixBlupType::EXTERIOR.' types (excluding prerequisites)', "\n",
            '========================================================================', "\n",
            '18: Initialize lambMeatIndexCoefficients', "\n",
            '19: Initialize wormResistanceIndexCoefficients', "\n",
            '========================================================================', "\n",
            '20: Validate ubnOfBirth format as !BLOCK in DataVruchtb.txt in mixblup cache folder', "\n",
            '21: Validate ubnOfBirth format as !BLOCK in PedVruchtb.txt in mixblup cache folder', "\n",
            '========================================================================', "\n",
            '30: Print separate csv files of latest breedValues for all ubns', "\n",
            '31: Print separate csv files of latest breedValues for chosen ubn', "\n",
            '========================================================================', "\n",
            '40: Clear excel cache folder', "\n",
            '41: Print CSV file for CF pedigree register', "\n",
            '42: Print CSV file for NTS, TSNH, LAX pedigree registers', "\n",
            '43: Print CSV file Breedvalues overview all animals on a ubn, with atleast one breedValue', "\n",
            '44: Print CSV file Breedvalues overview all animals on a ubn, even those without a breedValue', "\n",
            '========================================================================', "\n",
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
            case 14: $this->getBreedValuesResultTableUpdater()->update([MixBlupType::LAMB_MEAT_INDEX]); break;
            case 15: $this->getBreedValuesResultTableUpdater()->update([MixBlupType::FERTILITY]); break;
            case 16: $this->getBreedValuesResultTableUpdater()->update([MixBlupType::WORM]); break;
            case 17: $this->getBreedValuesResultTableUpdater()->update([MixBlupType::EXTERIOR]); break;

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


    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    private function updateAllResultTableValuesAndPrerequisites()
    {
        /*
         * Options
         */
        $updateBreedIndexes = $this->cmdUtil->generateConfirmationQuestion('Update BreedIndexes? (y/n, default is false)');
        $this->getLogger()->notice('Update BreedIndexes: '. StringUtil::getBooleanAsString($updateBreedIndexes));

        $updateNormalDistributions = $this->cmdUtil->generateConfirmationQuestion('Update NormalDistributions? (y/n, default is false)');
        $this->getLogger()->notice('Update NormalDistributions: '. StringUtil::getBooleanAsString($updateNormalDistributions));

        $generationDateString = $this->cmdUtil->generateQuestion('Insert custom GenerationDateString (default: The generationDateString of the last inserted breedValue will be used)', null);
        $this->getLogger()->notice('GenerationDateString to be used: '.$this->getBreedValuesResultTableUpdater()->getGenerationDateString($generationDateString));
        // End of options

        $this->getBreedValuesResultTableUpdater()->update(
            [],
            $updateBreedIndexes,
            $updateNormalDistributions,
            $generationDateString
        );
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
            $ubn = $this->cmdUtil->generateQuestion('insert ubn (default: '.self::DEFAULT_UBN.')', self::DEFAULT_UBN);
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
}
