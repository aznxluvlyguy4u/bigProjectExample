<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Employee;
use AppBundle\Entity\VsmIdGroup;
use AppBundle\Migration\AnimalTableMigrator;
use AppBundle\Migration\BlindnessFactorsMigrator;
use AppBundle\Migration\BreederNumberMigrator;
use AppBundle\Migration\CFToonVerhoevenMigrator;
use AppBundle\Migration\CompanySubscriptionMigrator;
use AppBundle\Migration\MigratorBase;
use AppBundle\Migration\MyoMaxMigrator;
use AppBundle\Migration\PerformanceMeasurementsMigrator;
use AppBundle\Migration\PredicatesMigrator;
use AppBundle\Migration\RacesMigrator;
use AppBundle\Migration\TagReplaceMigrator;
use AppBundle\Migration\UlnByAnimalIdMigrator;
use AppBundle\Migration\VsmIdGroupMigrator;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
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
    const CF_TOON_VERHOEVEN = 'cf_toon_verhoeven';

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

    /** @var string */
    private $rootDir;

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
            self::CF_TOON_VERHOEVEN => 'Overzicht_UK-dieren_CF_ToonVerhoeven.csv',
        );

        //Setup folders if missing
        $this->rootDir = $this->getContainer()->get('kernel')->getRootDir();
        NullChecker::createFolderPathsFromArrayIfNull($this->rootDir, $this->csvParsingOptions);
        
        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: Delete (test)animals with ulnCountryCode \'XD\'', "\n",
            '2: Update pedigreeRegisters', "\n",
            '3: Import AnimalTable csv file into database. Update PedigreeRegisters First!', "\n",
            '4: Set AnimalIds on current TagReplaces, THEN Migrate TagReplaces', "\n",
            '5: Import breederNumbers', "\n",
            '6: Import data for CF ToonVerhoeven', "\n",
            '7: Fix imported animalTable data', "\n",
            '----------------------------------------------------', "\n",
            '8: Migrate AnimalTable data', "\n",
            '9: Migrate Races', "\n",
            '10: Migrate MyoMax', "\n",
            '11: Migrate BlindnessFactor and update values in Animal', "\n",
            '12: Migrate Predicates and update values in Animal', "\n",
            '13: Migrate Performance Measurements', "\n",
            '14: Migrate Company SubscriptionDate', "\n",
            '----------------------------------------------------', "\n",
            '15: Export animal_migration_table to csv', "\n",
            '16: Import animal_migration_table from exported csv', "\n",
            '17: Export vsm_id_group to csv', "\n",
            '18: Import vsm_id_group from exported csv', "\n",
            '19: Export breeder_number to csv', "\n",
            '20: Import breeder_number from exported csv', "\n",
            '21: Export uln by animalId to csv', "\n",
            '22: Import uln by animalId to csv', "\n",
            '----------------------------------------------------', "\n",
            '23: Fix animal table after animalTable migration', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $result = $this->deleteTestAnimals() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 2:
                $result = $this->updatePedigreeRegister() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 3:
                $result = $this->importAnimalTableCsvFileIntoDatabase() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 4:
                $result = $this->migrateTagReplaces() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 5:
                $result = $this->migrateBreedNumbers() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 6:
                $result = $this->importCFAnimal() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 7:
                $result = $this->fixImportedAnimalTableData() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 8:
                $result = $this->migrateAnimalTable() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 9:
                $result = $this->migrateRaces() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 10:
                $result = $this->migrateMyoMax() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 11:
                $result = $this->migrateBlindnessFactors() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 12:
                $result = $this->migratePredicates() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 13:
                $result = $this->migratePerformanceMeasurements() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 14:
                $result = $this->migrateCompanySubscriptionDate() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 15:
                $result = $this->exportAnimalMigrationTableCsv() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 16:
                $result = $this->importAnimalMigrationTableCsv() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 17:
                $result = $this->exportVsmIdGroupCsv() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 18:
                $result = $this->importVsmIdGroupCsv() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 19:
                $result = $this->exportBreederNumberCsv() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 20:
                $result = $this->importBreederNumberCsv() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 21:
                $result = $this->exportUlnByAnimalId() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 22:
                $result = $this->importUlnByAnimalId() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 23:
                $result = $this->fixAnimalTable() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }


    /**
     * @param string $filename
     * @param bool $useImportFolder
     * @return array
     */
    private function parseCSV($filename, $useImportFolder = true) {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];

        $folderOption = $useImportFolder ? 'finder_in' : 'finder_out';

        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions[$folderOption])
            ->name($filename)
        ;

        $this->output->writeln('Parsing csv file...');

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
     * @param string $filename
     * @param bool $useImportFolder
     * @return array|null
     */
    private function parseCSVHeader($filename, $useImportFolder = true) {
        $folderOption = $useImportFolder ? 'finder_in' : 'finder_out';

        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions[$folderOption])
            ->name($filename)
        ;

        $this->output->writeln('Parsing csv file header...');

        foreach ($finder as $file) { $csv = $file; }

        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $data = fgetcsv($handle, null, ";");
            fclose($handle);
            gc_collect_cycles();
            return $data;
        }

        return null;
    }


    /**
     * @return bool
     */
    private function exportAnimalMigrationTableCsv()
    {
        $animalTableMigrator = new AnimalTableMigrator($this->cmdUtil, $this->em, $this->output, [], $this->rootDir);
        $this->output->writeln('Exporting animal_migration_table to csv');
        $animalTableMigrator->exportToCsv();
        return true;
    }


    /**
     * @return bool
     */
    private function importAnimalMigrationTableCsv()
    {
        $columnHeaders = $this->parseCSVHeader(AnimalTableMigrator::FILENAME_CSV_EXPORT, false);
        $data = $this->parseCSV(AnimalTableMigrator::FILENAME_CSV_EXPORT, false);
        if(count($data) == 0 && $columnHeaders != null) { return false; }

        $animalTableMigrator = new AnimalTableMigrator($this->cmdUtil, $this->em, $this->output, $data, $this->rootDir, $columnHeaders);
        $this->output->writeln('Importing animal_migration_table from csv');
        $animalTableMigrator->importFromCsv();
        return true;
    }


    /**
     * @return bool
     */
    private function exportVsmIdGroupCsv()
    {
        $vsmIdGroupMigrator = new VsmIdGroupMigrator($this->cmdUtil, $this->em, $this->output, [], $this->rootDir);
        $this->output->writeln('Exporting vsm_id_group to csv');
        $vsmIdGroupMigrator->exportToCsv();
        return true;
    }


    /**
     * @return bool
     */
    private function importVsmIdGroupCsv()
    {
        $columnHeaders = $this->parseCSVHeader(VsmIdGroupMigrator::FILENAME_CSV_EXPORT, false);
        $data = $this->parseCSV(VsmIdGroupMigrator::FILENAME_CSV_EXPORT, false);
        if(count($data) == 0 && $columnHeaders != null) { return false; }

        $vsmIdGroupMigrator = new VsmIdGroupMigrator($this->cmdUtil, $this->em, $this->output, $data, $this->rootDir, $columnHeaders);
        $this->output->writeln('Importing vsm_id_group from csv');
        $vsmIdGroupMigrator->importFromCsv();
        return true;
    }


    /**
     * @return bool
     */
    private function exportBreederNumberCsv()
    {
        $breederNumberMigrator = new BreederNumberMigrator($this->cmdUtil, $this->em, $this->output, [], $this->rootDir);
        $this->output->writeln('Exporting vsm_id_group to csv');
        $breederNumberMigrator->exportToCsv();
        return true;
    }


    /**
     * @return bool
     */
    private function importBreederNumberCsv()
    {
        $columnHeaders = $this->parseCSVHeader(BreederNumberMigrator::FILENAME_CSV_EXPORT, false);
        $data = $this->parseCSV(BreederNumberMigrator::FILENAME_CSV_EXPORT, false);
        if(count($data) == 0 && $columnHeaders != null) { return false; }

        $breederNumberMigrator = new BreederNumberMigrator($this->cmdUtil, $this->em, $this->output, $data, $this->rootDir, $columnHeaders);
        $this->output->writeln('Importing vsm_id_group from csv');
        $breederNumberMigrator->importFromCsv();
        return true;
    }


    /**
     * @return bool
     */
    private function exportUlnByAnimalId()
    {
        $unlNumberMigrator = new UlnByAnimalIdMigrator($this->cmdUtil, $this->em, $this->output, [], $this->rootDir);
        $this->output->writeln('Exporting ulns by animalId to csv');
        $unlNumberMigrator->exportToCsv();
        return true;
    }


    /**
     * @return bool
     */
    private function importUlnByAnimalId()
    {
        $columnHeaders = $this->parseCSVHeader(UlnByAnimalIdMigrator::FILENAME_CSV_EXPORT, false);
        $data = $this->parseCSV(UlnByAnimalIdMigrator::FILENAME_CSV_EXPORT, false);
        if(count($data) == 0 && $columnHeaders != null) { return false; }

        $unlNumberMigrator = new UlnByAnimalIdMigrator($this->cmdUtil, $this->em, $this->output, $data, $this->rootDir, $columnHeaders);
        $this->output->writeln('Importing ulns by animalId from csv');
        $unlNumberMigrator->updateUlnsFromCsv();
        return true;
    }


    /**
     * @return bool
     */
    private function deleteTestAnimals()
    {
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->em->getRepository(Animal::class);
        $animalRepository->deleteTestAnimal($this->output, $this->cmdUtil);
        return true;
    }


    /**
     * @return bool
     */
    private function importAnimalTableCsvFileIntoDatabase()
    {
        $data = $this->parseCSV($this->filenames[self::ANIMAL_TABLE]);
        if(count($data) == 0) { return false; }

        $animalTableMigrator = new AnimalTableMigrator($this->cmdUtil, $this->em, $this->output, $data, $this->rootDir);
        $animalTableMigrator->importAnimalTableCsvFileIntoDatabase();
        return true;
    }


    /**
     * @return bool
     */
    private function importCFAnimal()
    {
        $data = $this->parseCSV($this->filenames[self::CF_TOON_VERHOEVEN]);
        if(count($data) == 0) { return false; }

        $animalTableMigrator = new CFToonVerhoevenMigrator($this->cmdUtil, $this->em, $this->output, $data);
        $animalTableMigrator->migrate();
        return true;
    }


    /**
     * @return bool
     */
    private function fixImportedAnimalTableData()
    {
        $animalTableMigrator = new AnimalTableMigrator($this->cmdUtil, $this->em, $this->output, [], $this->rootDir);
        $animalTableMigrator->fixValuesInAnimalMigrationTable();
        //TODO
        return true;
    }


    /**
     * @return bool
     */
    private function setAnimalIdsOnCurrentTagReplaces()
    {
        $developer = $this->em->getRepository(Employee::class)->find(self::DEVELOPER_PRIMARY_KEY);
        $tagReplaceMigrator = new TagReplaceMigrator($this->cmdUtil, $this->em, $this->output, [], $developer);
        return $tagReplaceMigrator->setAnimalIdsOnDeclareTagReplaces();
    }


    /**
     * @return bool
     */
    private function migrateTagReplaces()
    {
        $result = $this->setAnimalIdsOnCurrentTagReplaces() ? 'AnimalIds set on current tagReplaces' : 'Current tagReplaces already have animalIds' ;
        $this->output->writeln($result);
        
        $data = $this->parseCSV($this->filenames[self::TAG_REPLACES]);
        if(count($data) == 0) { return false; }

        $developer = $this->em->getRepository(Employee::class)->find(self::DEVELOPER_PRIMARY_KEY);
        $tagReplaceMigrator = new TagReplaceMigrator($this->cmdUtil, $this->em, $this->output, $data, $developer);
        $tagReplaceMigrator->migrate();
        return true;
    }


    /**
     * @return bool
     */
    private function migrateBreedNumbers()
    {
        $data = $this->parseCSV($this->filenames[self::SUBSCRIPTIONS]);
        if(count($data) == 0) { return false; }
        
        $animalTableMigrator = new BreederNumberMigrator($this->cmdUtil, $this->em, $this->output, $data, $this->rootDir);
        $animalTableMigrator->migrate();
        return true;
    }


    /**
     * @return bool
     */
    private function updatePedigreeRegister()
    {
        $animalTableMigrator = new AnimalTableMigrator($this->cmdUtil, $this->em, $this->output, [], $this->rootDir);
        $animalTableMigrator->updatePedigreeRegister();
        return true;
    }


    /**
     * @return bool
     */
    private function migrateAnimalTable()
    {
        $animalTableMigrator = new AnimalTableMigrator($this->cmdUtil, $this->em, $this->output, [], $this->rootDir);
        $animalTableMigrator->migrate();

        return true;
    }
    
    
    /**
     * @return bool
     */
    private function migrateRaces()
    {
        $data = $this->parseCSV($this->filenames[self::RACES]);
        if(count($data) == 0) { return false; }

        $developer = $this->em->getRepository(Employee::class)->find(self::DEVELOPER_PRIMARY_KEY);
        $racesMigrator = new RacesMigrator($this->cmdUtil, $this->em, $this->output, $data, $developer);
        $racesMigrator->migrate();
        return true;
    }
    
    
    private function migrateMyoMax()
    {
        $data = $this->parseCSV($this->filenames[self::MYO_MAX]);
        if(count($data) == 0) { return false; }
        
        $myoMaxMigrator = new MyoMaxMigrator($this->cmdUtil, $this->em, $this->output, $data);
        $myoMaxMigrator->migrate();
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

        $migrateBlindnessFactors = new BlindnessFactorsMigrator($this->cmdUtil, $this->em, $this->output, $data);
        $migrateBlindnessFactors->migrate();
        return true;
    }


    /**
     * Note, it has already been checked that, no animal has more than one blindnessFactor
     *
     * @return bool
     */
    private function migratePredicates()
    {
        $data = $this->parseCSV($this->filenames[self::PREDICATES]);
        if(count($data) == 0) { return false; }

        $predicatesMigrator = new PredicatesMigrator($this->cmdUtil, $this->em, $this->output, $data);
        $predicatesMigrator->migrate();
        return true;
    }


    /**
     * @return bool
     */
    private function migratePerformanceMeasurements()
    {
        $data = $this->parseCSV($this->filenames[self::PERFORMANCE_MEASUREMENTS]);
        if(count($data) == 0) { return false; }
        
        /** @var PerformanceMeasurementsMigrator $migrator */
        $migrator = new PerformanceMeasurementsMigrator($this->cmdUtil, $this->em, $data, $this->rootDir, $this->output);
        return $migrator->isSuccessFull();
    }


    /**
     * @return bool
     */
    private function migrateCompanySubscriptionDate()
    {
        $data = $this->parseCSV($this->filenames[self::SUBSCRIPTIONS]);
        if(count($data) == 0) { return false; }

        $migrator = new CompanySubscriptionMigrator($this->cmdUtil, $this->em, $this->output, $data, $this->rootDir);
        $migrator->migrate();
        $migrator->printOutCsvOfCompaniesWithoutSubscriptionDate();
        return true;
    }


    private function fixAnimalTable()
    {
        $animalTableMigrator = new AnimalTableMigrator($this->cmdUtil, $this->em, $this->output, [], $this->rootDir);
        $this->output->writeln('Fixing animalTable');
        $animalTableMigrator->fixAnimalTableAfterImport();
        return true;
    }
}
