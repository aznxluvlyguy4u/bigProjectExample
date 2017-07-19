<?php


namespace AppBundle\Service;


use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class VsmMigratorService
 */
class VsmMigratorService
{
    const DEFAULT_OPTION = 0;
    const DEVELOPER_PRIMARY_KEY = 2151; //Used as the person that creates and edits imported data

    //FileName arrayKeys
    const ANIMAL_TABLE = 'animal_table';
    const BIRTH = 'birth';
    const EXTERIORS = 'exteriors';
    const LITTERS = 'litters';
    const PERFORMANCE_MEASUREMENTS = 'performance_measurements';
    const TAG_REPLACES = 'tag_replaces';
    const WORM_RESISTANCE = 'worm_resistance';

    /** @var EntityManagerInterface|ObjectManager */
    private $em;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var string */
    private $rootDir;

    /** @var array */
    private $filenames;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/',
        //'finder_name' => 'filename.csv',
        'ignoreFirstLine' => true
    );



    /**
     * VsmMigratorService constructor.
     * @param ObjectManager $em
     * @param string $rootDir
     */
    public function __construct(ObjectManager $em, $rootDir)
    {
        $this->em = $em;
        $this->rootDir = $rootDir;

        $this->filenames = array(
            self::ANIMAL_TABLE => '20170411_1022_Diertabel.csv',
            self::BIRTH => '20161007_1156_Diergeboortetabel.csv',
            self::EXTERIORS => '20170411_1022_Stamboekinspectietabel.csv',
            self::LITTERS => '20170411_1022_Reproductietabel_alleen_worpen.csv',
            self::PERFORMANCE_MEASUREMENTS => '20170411_1022_Dierprestatietabel.csv',
            self::TAG_REPLACES => '20170411_1022_DierOmnummeringen.csv',
            self::WORM_RESISTANCE => 'Uitslagen_IgA_2014-2015-2016_def_edited.csv',
        );
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function run(CommandUtil $cmdUtil)
    {
        $this->cmdUtil = $cmdUtil;
        $this->cmdUtil->writeln(DoctrineUtil::getDatabaseHostAndNameString($this->em));
        $this->cmdUtil->writeln('');

        //Setup folders if missing
        NullChecker::createFolderPathsFromArrayIfNull($this->rootDir, $this->csvParsingOptions);
    }



}