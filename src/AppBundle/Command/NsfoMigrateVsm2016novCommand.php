<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Employee;
use AppBundle\Migration\AnimalTableMigrator;
use AppBundle\Migration\BlindnessFactorsMigrator;
use AppBundle\Migration\MigratorBase;
use AppBundle\Migration\MyoMaxMigrator;
use AppBundle\Migration\PerformanceMeasurementsMigrator;
use AppBundle\Migration\PredicatesMigrator;
use AppBundle\Migration\RacesMigrator;
use AppBundle\Migration\TagReplaceMigrator;
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
        );

        //Setup folders if missing
        $this->rootDir = $this->getContainer()->get('kernel')->getRootDir();
        NullChecker::createFolderPathsFromArrayIfNull($this->rootDir, $this->csvParsingOptions);
        
        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Delete (test)animals with ulnCountryCode \'XD\'', "\n",
            '2: Update pedigreeRegisters', "\n",
            '3: Import AnimalTable csv file into database. Update PedigreeRegisters First!', "\n",
            '4: Set AnimalIds on current TagReplaces, THEN Migrate TagReplaces', "\n",
            '5: Fix imported animalTable data', "\n",
            '6: BLANK', "\n",
            '7: Migrate AnimalTable data', "\n",
            '8: Migrate Races', "\n",
            '9: Migrate MyoMax', "\n",
            '10: Migrate BlindnessFactor and update values in Animal', "\n",
            '11: Migrate Predicates and update values in Animal', "\n",
            '12: Migrate Performance Measurements', "\n",
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
                $result = $this->fixImportedAnimalTableData() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 6:
//                $result = $this->fixImportedAnimalTableData() ? 'DONE' : 'NO DATA!' ;
//                $output->writeln($result);
                break;

            case 7:
                $result = $this->migrateAnimalTable() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 8:
                $result = $this->migrateRaces() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 9:
                $result = $this->migrateMyoMax() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 10:
                $result = $this->migrateBlindnessFactors() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 11:
                $result = $this->migratePredicates() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            case 12:
                $result = $this->migratePerformanceMeasurements() ? 'DONE' : 'NO DATA!' ;
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
        $data = $this->parseCSV($this->filenames[self::ANIMAL_TABLE]);
        if(count($data) == 0) { return false; }

        $animalTableMigrator = new AnimalTableMigrator($this->cmdUtil, $this->em, $this->output, $data, $this->rootDir);
        $this->output->writeln('Fixing genders in the database');
        $animalTableMigrator->fixGendersInDatabase();
        $animalTableMigrator->verifyData();
//        $animalTableMigrator->migrate(); TODO

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
}
