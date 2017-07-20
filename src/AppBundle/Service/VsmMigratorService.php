<?php


namespace AppBundle\Service;


use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvParser;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\FilesystemUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class VsmMigratorService
 */
class VsmMigratorService
{
    const DEFAULT_OPTION = 0;
    const DEVELOPER_PRIMARY_KEY = 2151; //Used as the person that creates and edits imported data

    //CsvOptions
    const IMPORT_SUB_FOLDER = 'vsm2017jun/';

    //FileName arrayKeys
    const ANIMAL_TABLE = 'animal_table';
    const BIRTH = 'birth';
    const EXTERIORS = 'exteriors';
    const LITTERS = 'litters';
    const PERFORMANCE_MEASUREMENTS = 'performance_measurements';
    const RESIDENCE = 'residence';
    const TAG_REPLACES = 'tag_replaces';
    const WORM_RESISTANCE = 'worm_resistance';

    /** @var AnimalTableImporter */
    private $importer;

    /** @var EntityManagerInterface|ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var string */
    private $rootDir;

    /** @var array */
    private $filenames;
    /** @var CsvOptions */
    private $csvOptions;


    /**
     * VsmMigratorService constructor.
     * @param ObjectManager $em
     * @param string $rootDir
     * @param AnimalTableImporter $animalTableImporter
     */
    public function __construct(ObjectManager $em, $rootDir, AnimalTableImporter $animalTableImporter)
    {
        $this->em = $em;
        $this->conn = $this->em->getConnection();
        $this->rootDir = $rootDir;
        $this->importer = $animalTableImporter;

        $this->filenames = array(
            self::ANIMAL_TABLE => '20170411_1022_Diertabel.csv',
            self::BIRTH => '20161007_1156_Diergeboortetabel.csv',
            self::EXTERIORS => '20170411_1022_Stamboekinspectietabel.csv',
            self::LITTERS => '20170411_1022_Reproductietabel_alleen_worpen.csv',
            self::PERFORMANCE_MEASUREMENTS => '20170411_1022_Dierprestatietabel.csv',
            self::RESIDENCE => '20170411_1022_Diermutatietabel.csv',
            self::TAG_REPLACES => '20170411_1022_DierOmnummeringen.csv',
            self::WORM_RESISTANCE => 'Uitslagen_IgA_2014-2015-2016_def_edited.csv',
        );

        $this->csvOptions = (new CsvOptions())
            ->appendDefaultInputFolder(self::IMPORT_SUB_FOLDER)
            ->appendDefaultOutputFolder(self::IMPORT_SUB_FOLDER)
            ->ignoreFirstLine()
            ->setSemicolonSeparator()
            ;
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function run(CommandUtil $cmdUtil)
    {
        if($this->cmdUtil === null) { $this->cmdUtil = $cmdUtil; }

        $this->cmdUtil->writeln(DoctrineUtil::getDatabaseHostAndNameString($this->em));
        $this->cmdUtil->writeln('');

        //Setup folders if missing
        FilesystemUtil::createFolderPathsFromCsvOptionsIfNull($this->rootDir, $this->csvOptions);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: AnimalTableImporter options ...', "\n",
//            '2: Set AnimalIds on current TagReplaces, THEN Migrate TagReplaces', "\n",
//            '3: Fix imported animalTable data', "\n",
//            '----------------------------------------------------', "\n",
//            '4: Migrate AnimalTable data', "\n",
//            '13: Migrate Performance Measurements', "\n",
//            '----------------------------------------------------', "\n",
//            '15: Export animal_migration_table to csv', "\n",
//            '16: Import animal_migration_table from exported csv', "\n",
//            '17: Export vsm_id_group to csv', "\n",
//            '18: Import vsm_id_group from exported csv', "\n",
//            '21: Export uln by animalId to csv', "\n",
//            '22: Import uln by animalId to csv', "\n",
//            '----------------------------------------------------', "\n",
//            '23: Fix animal table after animalTable migration', "\n",
//            '24: Fix missing ulns by data in declares and migrationTable', "\n",
//            '25: Add missing animals to migrationTable', "\n",
//            '26: Fix duplicateDeclareTagTransfers', "\n",
//            '27: Fix vsmIds part1', "\n",
//            '28: Fix vsmIds part2', "\n",
//            '29: Migrate dateOfDeath & isAlive status', "\n",
//            '----------------------------------------------------', "\n",
//            '31: Migrate BirthWeights into weight and birthProgress into animal', "\n",
//            '39: Fill missing british ulnNumbers in AnimalMigrationTable', "\n",
//            '----------------------------------------------------', "\n",
//            '40: Fill missing ulnNumbers in AnimalMigrationTable', "\n",
//            '41: Fix animalIds in AnimalMigrationTable (likely incorrect due to duplicate fix)', "\n",
//            '42: Fix genderInDatabase values in AnimalMigrationTable (likely incorrect due to genderChange)', "\n",
//            '43: Fix parentId values in AnimalMigrationTable', "\n",
//            '44: Fix inverted primary and secondary vsmIds in the vsmIdGroup table', "\n",
//            '----------------------------------------------------', "\n",
//            '45: Migrate AnimalTable data V2', "\n",
//            '46: Migrate AnimalTable data: UPDATE Synced Animals', "\n",
//            '47: Fix missing pedigreeNumbers', "\n",
//            '48: Set missing parents on animal', "\n",
            '----------------------------------------------------', "\n",
            'other: Exit VsmMigrator', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->importer->run($this->cmdUtil); break;
//            case 2:
//                break;
//            case 3:
//                break;
//            case 4:
//                break;
//            case 5:
//                break;
//            case 6:
//                break;
//            case 7:
//                break;
//            case 8:
//                break;
//            case 9:
//                break;
//            case 10:
//                break;
//            case 11:
//                break;
//            case 12:
//                break;
//            case 13:
//                break;
//            case 14:
//                break;
//            case 15:
//                break;
//            case 16:
//                break;
//            case 17:
//                break;
            default: return;
        }
        $this->run($this->cmdUtil);
    }



    /**
     * @param string $filename
     * @return array
     */
    private function parseCSV($filename) {

        $this->csvOptions->setFileName($filename);
        return CsvParser::parse($this->csvOptions);
    }
}