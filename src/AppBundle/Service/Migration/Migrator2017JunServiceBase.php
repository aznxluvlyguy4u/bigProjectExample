<?php


namespace AppBundle\Service\Migration;

use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\Person;
use AppBundle\Entity\VsmIdGroup;
use AppBundle\Entity\VsmIdGroupRepository;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\CsvParser;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class Migrator2017JunServiceBase
 */
class Migrator2017JunServiceBase extends MigratorServiceBase
{
    const BATCH_SIZE = 5000;

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

    /** @var VsmIdGroupRepository */
    protected $vsmIdGroupRepository;

    /** @var array */
    protected $animalIdsByVsmId;
    /** @var array */
    protected $inspectorIdsInDbByFullName;
    /** @var array */
    protected $primaryVsmIdsBySecondaryVsmId;

    /**
     * Migrator2017JunServiceBase constructor.
     * @param ObjectManager $em
     * @param string $rootDir
     * @param int $batchSize
     * @param string $importSubFolder
     */
    public function __construct(ObjectManager $em, $rootDir, $batchSize = self::BATCH_SIZE, $importSubFolder = self::IMPORT_SUB_FOLDER)
    {
        parent::__construct($em, $batchSize, $importSubFolder, $rootDir);

        $this->filenames = array(
            self::ANIMAL_TABLE => '20170411_1022_Diertabel.csv',
            self::BIRTH => '20170411_1022_Diergeboortetabel.csv',
            self::EXTERIORS => '20170411_1022_Stamboekinspectietabel.csv',
            self::LITTERS => '20170411_1022_Reproductietabel_alleen_worpen.csv',
            self::PERFORMANCE_MEASUREMENTS => '20170411_1022_Dierprestatietabel.csv',
            self::RESIDENCE => '20170411_1022_Diermutatietabel.csv',
            self::TAG_REPLACES => '20170411_1022_DierOmnummeringen.csv',
            self::WORM_RESISTANCE => 'Uitslagen_IgA_2014-2015-2016-2017_def_edited.csv',
        );

        $this->vsmIdGroupRepository = $this->em->getRepository(VsmIdGroup::class);
    }


    /**
     *
     * @param string $ulnString
     * @return array
     */
    public static function parseUln($ulnString)
    {
        if(Validator::verifyUlnFormat($ulnString, true)) {
            $parts = explode(' ', $ulnString);
            $parts[0] = str_replace('GB', 'UK', $parts[0]);
        } else {
            $parts = [null, null];
        }

        return [
            JsonInputConstant::ULN_COUNTRY_CODE => $parts[0],
            JsonInputConstant::ULN_NUMBER => $parts[1],
        ];

    }


    /**
     * @param string $gender
     * @return string
     */
    public static function parseGender($gender)
    {
        //The only genders in the file are 'M' and 'V'
        switch ($gender) {
            case GenderType::M: return GenderType::MALE;
            case GenderType::V: return GenderType::FEMALE;
            default: return GenderType::NEUTER;
        }
    }


    /**
     * @param string $stnString
     * @return array
     */
    public static function parseStn($stnString)
    {
        if(Validator::verifyPedigreeCountryCodeAndNumberFormat($stnString, true)) {
            $parts = explode(' ', $stnString);
            $parts[0] = str_replace('GB', 'UK', $parts[0]);
        } else {
            $parts = [null, null];
        }

        return [
            JsonInputConstant::PEDIGREE_COUNTRY_CODE => $parts[0],
            JsonInputConstant::PEDIGREE_NUMBER => $parts[1],
        ];
    }


    protected function createInspectorSearchArrayAndInsertNewInspectors($inspectorColumnRank = 14)
    {
        $this->writeLn('Creating inspector search Array ...');

        DoctrineUtil::updateTableSequence($this->conn, [Person::getTableName()]);

        $this->inspectorIdsInDbByFullName = $this->getInspectorSearchArrayWithNameCorrections();

        $newInspectors = [];

        foreach ($this->data as $record) {
            $inspectorFullName = $record[$inspectorColumnRank];

            if ($inspectorFullName !== '' && !key_exists($inspectorFullName, $this->inspectorIdsInDbByFullName)
                && !key_exists($inspectorFullName, $newInspectors)) {
                $newInspectors[$inspectorFullName] = $inspectorFullName;
            }
        }

        if (count($newInspectors) === 0) {
            return;
        }

        $this->writeLn('Inserting '.count($newInspectors).' new inspectors ...');
        foreach ($newInspectors as $newInspectorFullName) {
            $nameParts = explode(' ', $newInspectorFullName, 2);
            $inspector = new Inspector();
            $inspector
                ->setFirstName($nameParts[0])
                ->setLastName($nameParts[1])
                ->setPassword('BLANK')
            ;
            $this->em->persist($inspector);
            $this->writeLn($inspector->getFullName());
        }
        $this->em->flush();

        $this->writeln(count($newInspectors) . ' new inspectors inserted (without inspectorCode nor authorization');
    }


    /**
     * @return array
     */
    public function getInspectorSearchArrayWithNameCorrections()
    {
        $csvOptions = (new CsvOptions())
            ->setFileName('inspector_name_corrections.csv')
            ->appendDefaultInputFolder('inspectors/')
            ->setSemicolonSeparator()
            ;

        $nameCorrections = CsvParser::parse($csvOptions);

        $sql = "SELECT id, TRIM(CONCAT(first_name,' ', last_name)) as full_name 
                FROM person WHERE type = 'Inspector' ORDER BY first_name, last_name";
        $inspectorIdsInDbByFullName = SqlUtil::groupSqlResultsOfKey1ByKey2('id', 'full_name', $this->conn->query($sql)->fetchAll());

        foreach ($nameCorrections as $record)
        {
            $oldFullName = $record[0];
            $newFirstName = $record[1];
            $newLastName = $record[2];

            $currentFullName = trim($newFirstName.' '.$newLastName);

            if (key_exists($currentFullName, $inspectorIdsInDbByFullName)
            && !key_exists($oldFullName, $inspectorIdsInDbByFullName)) {
                $inspectorIdsInDbByFullName[$oldFullName] = $inspectorIdsInDbByFullName[$currentFullName];
            }

        }

        return $inspectorIdsInDbByFullName;
    }

    protected function resetPrimaryVsmIdsBySecondaryVsmId()
    {
        $this->primaryVsmIdsBySecondaryVsmId = $this->vsmIdGroupRepository->getPrimaryVsmIdsBySecondaryVsmId();
    }
}