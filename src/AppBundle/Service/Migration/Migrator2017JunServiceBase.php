<?php


namespace AppBundle\Service\Migration;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\GenderType;
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
            self::BIRTH => '20161007_1156_Diergeboortetabel.csv',
            self::EXTERIORS => '20170411_1022_Stamboekinspectietabel.csv',
            self::LITTERS => '20170411_1022_Reproductietabel_alleen_worpen.csv',
            self::PERFORMANCE_MEASUREMENTS => '20170411_1022_Dierprestatietabel.csv',
            self::RESIDENCE => '20170411_1022_Diermutatietabel.csv',
            self::TAG_REPLACES => '20170411_1022_DierOmnummeringen.csv',
            self::WORM_RESISTANCE => 'Uitslagen_IgA_2014-2015-2016_def_edited.csv',
        );
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
}