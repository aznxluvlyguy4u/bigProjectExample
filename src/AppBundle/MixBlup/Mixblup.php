<?php

namespace AppBundle\MixBlup;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BreedCode;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\WeightType;
use AppBundle\Migration\BreedCodeReformatter;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class Mixblup
{
    //Formatting
    const BLOCK_NULL_FILLER = 3;
    const ULN_NULL_FILLER = 0;
    const BREED_CODE_NULL_FILLER = 'N_B';
    const BREED_CODE_PARTS_NULL_FILLER = -99;
    const BREED_TYPE_NULL_FILLER = 'N_B';
    const SCRAPIE_GENOTYPE_NULL_FILLER = 'N_B';
    const NLING_NULL_FILLER = -99;
    const LITTER_GROUP_NULL_FILLER = 'N_B';
    const DATE_OF_BIRTH_NULL_FILLER = 'N_B';
    const MEASUREMENT_DATE_NULL_FILLER = 'N_B';
    const FAT_NULL_FILLER = -99;
    const MUSCLE_THICKNESS_NULL_FILLER = -99;
    const TAIL_LENGTH_NULL_FILLER = -99;
    const WEIGHT_NULL_FILLER = -99;
    const EXTERIOR_NULL_FILLER = -99;
    const EXTERIOR_KIND_NULL_FILLER = 'N_B';
    const UBN_NULL_FILLER = 0; //Value is used as default value for !BLOCK
    const HETEROSIS_NULL_FILLER = -99;
    const RECOMBINATION_NULL_FILLER = -99;
    const AGE_NULL_FILLER = -99;
    const GROWTH_NULL_FILLER = -99;
    const TOTAL_BORN_COUNT_NULL_FILLER = -99;
    const STILLBORN_COUNT_NULL_FILLER = -99;
    const YEAR_UBN_NULL_FILLER = 'N_B';
    const RUT_INDUCTION_NULL_FILLER = 'N_B';
    const PERMANENT_ENVIRONMENT_NULL_FILLER = 'N_B';
    const INSPECTOR_CODE_NULL_FILLER = 'N_B';
    const PRECOCIOUS_NULL_FILLER = 'N_B'; //vroegrijp
    const BIRTH_PROCESS_NULL_FILLER = 'N_B';
    const RAM = 'ram';
    const EWE = 'ooi';
    const GENDER_NULL_FILLER = 'N_B';
    const NEUTER = 'N_B';

    const DECIMAL_SYMBOL = '.';

    //Rounding Accuracies
    const HETEROSIS_AND_RECOMBINATION_ROUNDING_ACCURACY = 2;

    //Column padding & widths
    const COLUMN_PADDING_SIZE = 1;
    const COLUMN_WIDTH_GENDER = 6;
    const COLUMN_WIDTH_FAT = 5;
    const COLUMN_WIDTH_TAIL_LENGTH = 5;
    const COLUMN_WIDTH_MUSCLE_THICKNESS = 5;
    const COLUMN_WIDTH_DATE = 8;
    const COLUMN_WIDTH_BLOCK = 10;
    const COLUMN_WIDTH_EXTERIOR = 4;
    const COLUMN_WIDTH_ULN = 14;
    const COLUMN_WIDTH_YEAR_AND_UBN = 14;
    const COLUMN_WIDTH_BREED_CODE_PART = 3;
    const COLUMN_WIDTH_BREED_CODE = 16;
    const COLUMN_WIDTH_BREED_TYPE = 16;
    const COLUMN_WIDTH_GENOTYPE = 7;
    const COLUMN_WIDTH_NLING = 3;
    const COLUMN_WIDTH_LITTER_GROUP = 17;
    const COLUMN_WIDTH_HETEROSIS = 4;
    const COLUMN_WIDTH_RECOMBINATION = 4;
    const COLUMN_WIDTH_AGE = 5;
    const COLUMN_WIDTH_GROWTH = 7;
    const COLUMN_WIDTH_WEIGHT = 6;


    const ANIMAL = 'ANIMAL';
    const MEASUREMENT_DATE = 'MEASUREMENT_DATE';
    const BODY_FAT = 'BODY_FAT';
    const MUSCLE_THICKNESS = 'MUSCLE_THICKNESS';
    const TAIL_LENGTH = 'TAIL_LENGTH';
    const WEIGHT = 'WEIGHT';
    const CONTRADICTING_DUPLICATES = 'CONTRADICTING_DUPLICATES';
    const ROW_DATA = 'ROW_DATA';
    const DATE_OF_BIRTH = 'DATE_OF_BIRTH';

    //Filename strings
    const TEST_ATTRIBUTES = 'toets_kenmerken';
    const EXTERIOR_ATTRIBUTES = 'exterieur_kenmerken';
    const FERTILITY = 'vruchtbaarheid';
    const ERRORS = 'errors';

    //Validation Limits
    const EXTERIOR_VALUE_LIMIT = 100.0;

    //Versions
    const IS_GROUP_BY_ANIMAL_AND_MEASUREMENT_DATE = true;

    /** @var ObjectManager */
    private $em;

    /** @var array */
    private $animals;

    /** @var array */
    private $measurementCodes;

    /** @var Collection */
    private $exteriorMeasurements;

    /** @var string */
    private $errorsFilePath;

    /** @var string */
    private $instructionsFilePath;

    /** @var string */
    private $instructionsFilePathTestAttributes;

    /** @var string */
    private $instructionsFilePathExteriorAttributes;

    /** @var string */
    private $instructionsFilePathFertilityAttributes;

    /** @var string */
    private $dataFilePath;

    /** @var string */
    private $pedigreeFilePath;

    /** @var string */
    private $dataFilePathTestAttributes;

    /** @var string */
    private $dataFilePathExteriorAttributes;

    /** @var string */
    private $dataFilePathFertilityAttributes;

    /** @var int */
    private $firstMeasurementYear;

    /** @var int */
    private $lastMeasurementYear;

    /** @var boolean */
    private $isGetAllMeasurements;

    /** @var ArrayCollection */
    private $animalRowBases;

    /** @var ArrayCollection $testAttributes */
    private $testAttributes;
    
    /** @var CommandUtil */
    private $cmdUtil;

    /** @var WeightRepository $weightRepository */
    private $weightRepository;

    /** @var OutputInterface */
    private $output;

    /** @var Connection */
    private $conn;


    /**
     * Mixblup constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     * @param int $firstMeasurementYear
     * @param int $lastMeasurementYear
     * @param CommandUtil $cmdUtil
     * @param array $animals
     * @param OutputInterface $output
     */
    public function __construct(ObjectManager $em, $outputFolderPath, $firstMeasurementYear, $lastMeasurementYear, $cmdUtil, $animals = null, $output)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->animalRowBases = new ArrayCollection();
        $this->testAttributes = new ArrayCollection();
        $this->measurementCodes = array();
        $this->cmdUtil = $cmdUtil;
        $this->output = $output;

        if($firstMeasurementYear == null && $lastMeasurementYear == null) {
            $this->isGetAllMeasurements = true;
        } else {
            $this->isGetAllMeasurements = false;
        }

        $this->firstMeasurementYear = $firstMeasurementYear;
        $this->lastMeasurementYear = $lastMeasurementYear;

        $this->weightRepository = $this->em->getRepository(Weight::class);

        if($animals != null) {
            $this->animals = $animals;
        }

        $dataFileName = MixBlupSetting::DATA_FILENAME;
        $pedigreeFileName = MixBlupSetting::PEDIGREE_FILENAME;
        $instructionsFileName = MixBlupSetting::INSTRUCTIONS_FILENAME;

        if(substr($outputFolderPath, -1) != '/') {
            $this->dataFilePath = $outputFolderPath.'/'.$dataFileName.'.txt';
            $this->dataFilePathTestAttributes = $outputFolderPath.'/'.$dataFileName.'_'.self::TEST_ATTRIBUTES.'.txt';
            $this->dataFilePathExteriorAttributes = $outputFolderPath.'/'.$dataFileName.'_'.self::EXTERIOR_ATTRIBUTES.'.txt';
            $this->dataFilePathFertilityAttributes = $outputFolderPath.'/'.$dataFileName.'_'.self::FERTILITY.'.txt';
            $this->pedigreeFilePath = $outputFolderPath.'/'.$pedigreeFileName.'.txt';
            $this->errorsFilePath = $outputFolderPath.'/'.self::ERRORS.'.txt';
            $this->instructionsFilePath = $outputFolderPath.'/'.$instructionsFileName.'.inp';
            $this->instructionsFilePathTestAttributes = $outputFolderPath.'/'.$instructionsFileName.'_'.self::TEST_ATTRIBUTES.'.inp';
            $this->instructionsFilePathExteriorAttributes = $outputFolderPath.'/'.$instructionsFileName.'_'.self::EXTERIOR_ATTRIBUTES.'.inp';
            $this->instructionsFilePathFertilityAttributes = $outputFolderPath.'/'.$instructionsFileName.'_'.self::FERTILITY.'.inp';
        } else {
            $this->dataFilePath = $outputFolderPath.$dataFileName.'.txt';
            $this->dataFilePathTestAttributes = $outputFolderPath.$dataFileName.'_'.self::TEST_ATTRIBUTES.'.txt';
            $this->dataFilePathExteriorAttributes = $outputFolderPath.$dataFileName.'_'.self::EXTERIOR_ATTRIBUTES.'.txt';
            $this->dataFilePathFertilityAttributes = $outputFolderPath.$dataFileName.'_'.self::FERTILITY.'.txt';
            $this->pedigreeFilePath = $outputFolderPath.$pedigreeFileName.'.txt';
            $this->errorsFilePath = $outputFolderPath.self::ERRORS.'.txt';
            $this->instructionsFilePath = $outputFolderPath.$instructionsFileName.'.inp';
            $this->instructionsFilePathTestAttributes = $outputFolderPath.$instructionsFileName.'_'.self::TEST_ATTRIBUTES.'.inp';
            $this->instructionsFilePathExteriorAttributes = $outputFolderPath.$instructionsFileName.'_'.self::EXTERIOR_ATTRIBUTES.'.inp';
            $this->instructionsFilePathFertilityAttributes = $outputFolderPath.$instructionsFileName.'_'.self::FERTILITY.'.inp';
        }

    }


    /**
     * Only retrieve the animals when they are really needed.
     * And for duplicate animals, choose the one with a higher number primary key. Those are the imported animals with more data.
     * @return array
     */
    private function getAnimalsArrayIfNull()
    {
        if($this->animals == null) {

            $sql = "SELECT CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(f.uln_country_code, f.uln_number) as uln_father, CONCAT(m.uln_country_code, m.uln_number) as uln_mother, a.breed_code, a.gender, a.date_of_birth, a.mixblup_block as block FROM animal a LEFT JOIN animal f ON a.parent_father_id = f.id LEFT JOIN animal m ON a.parent_mother_id = m.id INNER JOIN (SELECT MAX(id) as id FROM animal GROUP BY uln_country_code, uln_number) b ON a.id = b.id;";
            $this->animals = $this->conn->query($sql)->fetchAll();
        }
        return $this->animals;
    }


    private function getTestMeasurementsBySql()
    {
        if($this->isGetAllMeasurements) {
            $sql = "SELECT DISTINCT(animal_id_and_date) as code FROM measurement m WHERE (type = 'BodyFat' OR type = 'Weight' OR type = 'MuscleThickness' OR type = 'TailLength')";

        } else {
            $startDate = $this->firstMeasurementYear.'-01-01';
            $endDate = ($this->lastMeasurementYear+1).'-01-01';

            $sql = "SELECT DISTINCT(animal_id_and_date) as code FROM measurement m WHERE measurement_date BETWEEN '".$startDate."' AND '".$endDate."' AND (type = 'BodyFat' OR type = 'Weight' OR type = 'MuscleThickness' OR type = 'TailLength')";
        }

        $results = $this->conn->query($sql)->fetchAll();

        foreach($results as $result) {
            $code = $result['code'];
            $this->measurementCodes[$code] = $code;
        }
    }


    /**
     * Only retrieve the exterior measurements when they are really needed.
     * @return Collection
     */
    private function getExteriorMeasurementsIfNull()
    {
        /** @var ExteriorRepository $exteriorRepository */
        $exteriorRepository = $this->em->getRepository(Exterior::class);

        if($this->isGetAllMeasurements) {
            $this->output->writeln('Retrieving all exterior measurements...');
            $this->exteriorMeasurements = $exteriorRepository->findAll();
            
        } else {
            $this->output->writeln('Retrieving exterior measurements between '.$this->firstMeasurementYear.' and '.$this->lastMeasurementYear.' ...');
            $this->exteriorMeasurements = $exteriorRepository->getExteriorsBetweenYears($this->firstMeasurementYear, $this->lastMeasurementYear);
        }

        return $this->exteriorMeasurements;
    }


    /**
     * @return array
     */
    public function generateInstructionArrayTestAttributes()
    {
        return [
            'TITEL   schapen fokwaarde berekening groei, spierdikte en vetbedekking',
            ' DATAFILE  '.MixBlupSetting::DATA_FILENAME.'_'.self::TEST_ATTRIBUTES.'.txt',
            ' animal     A !missing '.self::ULN_NULL_FILLER.' #uln',  //uln
            ' gender     A !missing '.self::GENDER_NULL_FILLER,  //ram/ooi/N_B
            ' jaarUbn    A !missing '.self::YEAR_UBN_NULL_FILLER.' #jaar en ubn van geboorte', //year and ubn of birth
            ' rascode    A !missing '.self::BREED_CODE_NULL_FILLER,  //breedCode
            ' rasstatus  A !missing '.self::BREED_TYPE_NULL_FILLER,  //breedType
            ' CovTE      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,  //TE, BT, DK are genetically all the same
            ' CovCF      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovNH      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovSW      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovOV      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,  //other  (NN means unknown)
            ' CovHet     T !missing '.self::HETEROSIS_NULL_FILLER.' #Heterosis van het dier',  //other  (NN means unknown)
            ' CovRec     T !missing '.self::RECOMBINATION_NULL_FILLER.' #Recombinatie van het dier',  //other  (NN means unknown)
            ' scrgen     A !missing '.self::SCRAPIE_GENOTYPE_NULL_FILLER.' #scrapiegenotype',
            ' n-ling     I !missing '.self::NLING_NULL_FILLER.' #worp grootte',  //Litter->size()
            ' worpnr     A !missing '.self::LITTER_GROUP_NULL_FILLER.' #worpnummer',  //worpnummer/litterGroup
            ' moeder     A !missing '.self::ULN_NULL_FILLER.' #uln van moeder',  //uln of mother
            ' father     A !missing '.self::ULN_NULL_FILLER.' #uln van vader',  //uln of father
            ' meetdatum  A !missing '.self::MEASUREMENT_DATE_NULL_FILLER, //measurementDate
            ' leeftijd   I !missing '.self::DATE_OF_BIRTH_NULL_FILLER.' #op moment van meting in dagen', //age of animal on measurementDate in days
            ' groei      T !missing '.self::GROWTH_NULL_FILLER.' #gewicht(kg)/leeftijd(dagen) op moment van meting', //growth weight(kg)/age(days) on measurementDate
            ' gebgewicht T !missing '.self::WEIGHT_NULL_FILLER.' #geboortegewicht',   //weight at birth
            ' gew8wk     T !missing '.self::WEIGHT_NULL_FILLER.' #8 weken gewichtmeting', //weight measurement at 8 weeks
            ' gew20wk    T !missing '.self::WEIGHT_NULL_FILLER.' #20 weken gewichtmeting', //weight measurement at 20 weeks
            ' vet1       T !missing '.self::FAT_NULL_FILLER,
            ' vet2       T !missing '.self::FAT_NULL_FILLER,
            ' vet3       T !missing '.self::FAT_NULL_FILLER,
            ' spierdik   T !missing '.self::MUSCLE_THICKNESS_NULL_FILLER.' #spierdikte',
            ' staartlg   T !missing '.self::TAIL_LENGTH_NULL_FILLER.' #staartlengte', //tailLength
            ' block I !BLOCK', //NOTE it is an integer here
            ' ',
            'PEDFILE   '.MixBlupSetting::PEDIGREE_FILENAME,
            ' animal    A !missing '.self::ULN_NULL_FILLER.' #uln',
            ' sire      A !missing '.self::ULN_NULL_FILLER.' #uln van vader',
            ' dam       A !missing '.self::ULN_NULL_FILLER.' #uln van moeder',
            ' block     I !BLOCK', //NOTE it is an integer here
            ' gender    A !missing '.self::GENDER_NULL_FILLER,
            ' gebjaar   A !missing '.self::DATE_OF_BIRTH_NULL_FILLER.' #geboortedatum',
            ' rascode   A !missing '.self::BREED_CODE_NULL_FILLER,
            ' ',
            'PARFILE  *insert par file reference here*',
            ' ',
            'MODEL    *insert model settings here*',
            ' ',
            'SOLVING  *insert solve settings here*'

        ];
    }

    /**
     * @return array
     */
    public function generateInstructionArrayExteriorAttributes()
    {
        return [
            'TITEL   schapen fokwaarde berekening exterieur',
            ' DATAFILE  '.MixBlupSetting::DATA_FILENAME.'_'.self::EXTERIOR_ATTRIBUTES.'.txt',
            ' animal     A !missing '.self::ULN_NULL_FILLER.' #uln',  //uln
            ' gender     A !missing '.self::GENDER_NULL_FILLER.' #sekse van dier',  //ram/ooi/0
            ' jaarUbn    A !missing '.self::YEAR_UBN_NULL_FILLER.' #jaar en ubn van geboorte', //year and ubn of birth
//            ' Inspectr   A !missing '.self::INSPECTOR_CODE_NULL_FILLER.' #code van NSFO inspecteur',  //breedCode TODO ALSO MATCH WITH DATA OUTPUT
            ' CovTE      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,  //TE, BT, DK are genetically all the same
            ' CovSW      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovBM      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovOV      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,  //other  (NN means unknown)
            ' CovHet     T !missing '.self::HETEROSIS_NULL_FILLER.' #Heterosis van het dier',  //other  (NN means unknown)
            ' CovRec     T !missing '.self::RECOMBINATION_NULL_FILLER.' #Recombinatie van het dier',  //other  (NN means unknown)
            ' meetdatum  A !missing '.self::MEASUREMENT_DATE_NULL_FILLER, //measurementDate
            ' SOORT A !missing '.self::EXTERIOR_KIND_NULL_FILLER.' #soort meting', //kind of external measurement
            ' KOP T !missing '.self::EXTERIOR_NULL_FILLER.' #kop', //skull
            ' BES T !missing '.self::EXTERIOR_NULL_FILLER.' #bespiering', //muscularity
            ' EVE T !missing '.self::EXTERIOR_NULL_FILLER.' #evenredigheid', //proportion
            ' ONT T !missing '.self::EXTERIOR_NULL_FILLER.' #ontwikkeling', //progress
            ' TYP T !missing '.self::EXTERIOR_NULL_FILLER.' #type', //(exterior)type
            ' BEE T !missing '.self::EXTERIOR_NULL_FILLER.' #beenwerk', //legWork
            ' VAC T !missing '.self::EXTERIOR_NULL_FILLER.' #vacht', //fur
            ' ALG T !missing '.self::EXTERIOR_NULL_FILLER.' #algemene voorkoming', //general appearance
            ' SHT T !missing '.self::EXTERIOR_NULL_FILLER.' #schofthoogte', //height
            ' LGT T !missing '.self::EXTERIOR_NULL_FILLER.' #romplengte', //length
            ' BDP T !missing '.self::EXTERIOR_NULL_FILLER.' #borstdiepte', //breast depth
            ' KEN T !missing '.self::EXTERIOR_NULL_FILLER.' #kenmerken', //markings
            ' block I !BLOCK', //NOTE it is an integer here
            ' ',
            'PEDFILE   '.MixBlupSetting::PEDIGREE_FILENAME,
            ' animal    A !missing '.self::ULN_NULL_FILLER.' #uln',
            ' sire      A !missing '.self::ULN_NULL_FILLER.' #uln van vader',
            ' dam       A !missing '.self::ULN_NULL_FILLER.' #uln van moeder',
            ' block     I !BLOCK', //NOTE it is an integer here
            ' gender    A !missing '.self::GENDER_NULL_FILLER,
            ' gebjaar   A !missing '.self::DATE_OF_BIRTH_NULL_FILLER.' #geboortedatum',
            ' rascode   A !missing '.self::BREED_CODE_NULL_FILLER,
            ' ',
            'PARFILE  *insert par file reference here*',
            ' ',
            'MODEL    *insert model settings here*',
            ' ',
            'SOLVING  *insert solve settings here*'

        ];
    }


    /**
     * @return array
     */
    public function generateInstructionArrayFertility()
    {
        return [
            'TITEL   schapen fokwaarde berekening vruchtbaarheid',
            ' DATAFILE  '.MixBlupSetting::DATA_FILENAME.'_'.self::FERTILITY.'.txt',
            ' animal     A !missing '.self::ULN_NULL_FILLER.' #uln',  //uln
            ' pariteit   A !missing '.self::DATE_OF_BIRTH_NULL_FILLER.' #Leeftijd ooi bij werpen in hele jaren',  //ram/ooi/0
            ' jaarUbn    A !missing '.self::YEAR_UBN_NULL_FILLER.' #jaar en ubn van geboorte', //year and ubn of birth
            ' CovTE      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,  //TE, BT, DK are genetically all the same
            ' CovCF      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovSW      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovNH      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovGP      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovBM      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovOV      I !missing '.self::BREED_CODE_PARTS_NULL_FILLER,
            ' CovHet     T !missing '.self::HETEROSIS_NULL_FILLER.' #Heterosis van dier of lam',
            ' CovRec     T !missing '.self::RECOMBINATION_NULL_FILLER.' #Recombinatie van dier of lam',
            ' M_CovHet   T !missing '.self::HETEROSIS_NULL_FILLER.' #Heterosis van moeder',
            ' M_CovRec   T !missing '.self::RECOMBINATION_NULL_FILLER.' #Recombinatie van moeder',
            ' M_TE       I !missing '.self::BREED_CODE_PARTS_NULL_FILLER.' #Rasdeel TE moeder',
            ' Bronst     A !missing '.self::RUT_INDUCTION_NULL_FILLER.' #Bronst inductie',
            ' Milieu     A !missing '.self::PERMANENT_ENVIRONMENT_NULL_FILLER.' #Permanent milieu',
            ' Moeder     A !missing '.self::ULN_NULL_FILLER.' #uln van moeder',  //uln of mother
            ' Geb_tot    I !missing '.self::TOTAL_BORN_COUNT_NULL_FILLER.' #totaal geboren',
            ' Geb_dood   I !missing '.self::STILLBORN_COUNT_NULL_FILLER.' #dood geboren',
            ' Vroegrijp  I !missing '.self::PRECOCIOUS_NULL_FILLER.' #1 als ooi worp heeft op eenjarige leeftijd, anders 0',
            ' Gebgew     T !missing '.self::WEIGHT_NULL_FILLER.' #geboortegewicht',
            ' Gebvrlp    I !missing '.self::BIRTH_PROCESS_NULL_FILLER.' #zonder=0, licht=1, normaal=2, zwaar=3, keizersnee=4',
            ' block I !BLOCK', //NOTE it is an integer here
            ' ',
            'PEDFILE   '.MixBlupSetting::PEDIGREE_FILENAME,
            ' animal    A !missing '.self::ULN_NULL_FILLER.' #uln',
            ' sire      A !missing '.self::ULN_NULL_FILLER.' #uln van vader',
            ' dam       A !missing '.self::ULN_NULL_FILLER.' #uln van moeder',
            ' block     I !BLOCK', //NOTE it is an integer here
            ' gender    A !missing '.self::GENDER_NULL_FILLER,
            ' gebjaar   A !missing '.self::DATE_OF_BIRTH_NULL_FILLER.' #geboortedatum',
            ' rascode   A !missing '.self::BREED_CODE_NULL_FILLER,
            ' ',
            'PARFILE  *insert par file reference here*',
            ' ',
            'MODEL    *insert model settings here*',
            ' ',
            'SOLVING  *insert solve settings here*'

        ];
    }


    public function generateInstructionFiles()
    {
        foreach($this->generateInstructionArrayTestAttributes() as $row) {
            file_put_contents($this->instructionsFilePathTestAttributes, $row."\n", FILE_APPEND);
        }

        foreach($this->generateInstructionArrayExteriorAttributes() as $row) {
            file_put_contents($this->instructionsFilePathExteriorAttributes, $row."\n", FILE_APPEND);
        }

        foreach($this->generateInstructionArrayFertility() as $row) {
            file_put_contents($this->instructionsFilePathFertilityAttributes, $row."\n", FILE_APPEND);
        }
    }


    public function generatePedigreeFile()
    {
        $this->getAnimalsArrayIfNull();

        $this->cmdUtil->setStartTimeAndPrintIt(count($this->animals) + 1, 1, 'Generating pedigree file...');

        $pedigreeRecords = new ArrayCollection();
        foreach ($this->animals as $animalArray) {
            $row = $this->writePedigreeRecord($animalArray);

            if($row == null) {
                file_put_contents($this->pedigreeFilePath.'errors.txt', $animalArray['uln']."\n", FILE_APPEND);

            } elseif($pedigreeRecords->contains($row)) {
                file_put_contents($this->pedigreeFilePath.'duplicates.txt', $row."\n", FILE_APPEND);

            } else {
                file_put_contents($this->pedigreeFilePath, $row."\n", FILE_APPEND);
                $pedigreeRecords->add($row);
            }

            $this->cmdUtil->advanceProgressBar(1, 'Generating pedigree file...');
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    public function generateExteriorMeasurementsDataFiles()
    {
        $this->validateMeasurementData();

        //ExteriorMeasurements
        $this->getExteriorMeasurementsIfNull();

        $message = 'Generating exterior measurements...';
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->exteriorMeasurements)+1, 1, $message);

        $exteriorAttributesRows  = new ArrayCollection();
        /** @var Exterior $exteriorMeasurement */
        foreach ($this->exteriorMeasurements as $exteriorMeasurement) {
            $row = $this->writeDataRecordExteriorAttributes($exteriorMeasurement);

            if($row == null) {
                if($exteriorMeasurement instanceof Exterior) {
                    $row = 'measuremendId: '.$exteriorMeasurement->getId();
                } else {
                    $row = 'non-Exterior measurement';
                }
                file_put_contents($this->dataFilePathExteriorAttributes.'errors.txt', $row."\n", FILE_APPEND);
            } elseif($exteriorAttributesRows->contains($row)) {
                file_put_contents($this->dataFilePathExteriorAttributes.'duplicates.txt', $row."\n", FILE_APPEND);
            } else {
                file_put_contents($this->dataFilePathExteriorAttributes, $row."\n", FILE_APPEND);
                $exteriorAttributesRows->add($row);
            }

            $this->cmdUtil->advanceProgressBar(1, $message);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    public function generateTestAttributeMeasurementsDataFiles()
    {
        $this->validateMeasurementData();

        //TestAttributeMeasurements
        $this->getTestMeasurementsBySql();
        $testMeasurementsCount = count($this->measurementCodes);
        $message = 'Generating test measurements...';
        $isSkipConflictingMeasurements = true; //NOTE Duplicates and Contradicting measurements have to been fixed separately.

        $this->cmdUtil->setStartTimeAndPrintIt($testMeasurementsCount+1, 1, $message);
        $testAttributeRows = new ArrayCollection();
        /** @var string $code */
        foreach ($this->measurementCodes as $code) {
            $row = $this->writeDataRecordTestAttributes($code, $isSkipConflictingMeasurements);
            if($row == null) {
                $row = 'animalIdAndDate: '.$code;
                file_put_contents($this->dataFilePathTestAttributes.'errors.txt', $row."\n", FILE_APPEND);
            } elseif($testAttributeRows->contains($row)) {
                file_put_contents($this->dataFilePathTestAttributes.'duplicates.txt', $row."\n", FILE_APPEND);
            } else {
                file_put_contents($this->dataFilePathTestAttributes, $row."\n", FILE_APPEND);
                $testAttributeRows->add($row);
            }
            $this->cmdUtil->advanceProgressBar(1, $message);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    public function generateFertilityDataFiles()
    {
        $this->validateMeasurementData();

        //TODO add fertility measurements
    }


    /**
     * @param array $animalArray
     * @return string
     */
    private function writePedigreeRecord(array $animalArray)
    {
        $animalUln = Utils::fillNullOrEmptyString($animalArray['uln'], self::ULN_NULL_FILLER);
        $motherUln = Utils::fillNullOrEmptyString($animalArray['uln_mother'], self::ULN_NULL_FILLER);
        $fatherUln = Utils::fillNullOrEmptyString($animalArray['uln_father'], self::ULN_NULL_FILLER);

        $isUlnChildMissing = NullChecker::isNull($animalArray['uln']);
        if($isUlnChildMissing) {
            //skip record
            return null;
        }

        $breedCode = Utils::fillNullOrEmptyString($animalArray['breed_code'], self::BREED_CODE_NULL_FILLER);
        $gender = Utils::fillNullOrEmptyString(self::formatGender($animalArray['gender']));
        $dateOfBirthString = Utils::fillNullOrEmptyString(self::formatDateOfBirthString($animalArray['date_of_birth']));

        $block = Utils::fillNullOrEmptyString($animalArray['block'], self::BLOCK_NULL_FILLER);

        $record =
        Utils::addPaddingToStringForColumnFormatSides($animalUln, self::COLUMN_WIDTH_ULN)
        .Utils::addPaddingToStringForColumnFormatCenter($fatherUln, self::COLUMN_WIDTH_ULN, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($motherUln, self::COLUMN_WIDTH_ULN, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($block, self::COLUMN_WIDTH_BLOCK, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($gender, self::COLUMN_WIDTH_GENDER, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($dateOfBirthString, self::COLUMN_WIDTH_DATE, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatSides($breedCode, self::COLUMN_WIDTH_BREED_CODE, false)
        ;

        return $record;
    }


    /**
     * @param array $results
     * @param boolean $isSkipConflictingMeasurements
     * @param string $label
     * @return float|int|null
     */
    private function getMeasurementFromSqlResults($results, $isSkipConflictingMeasurements, $label)
    {
        $isGetFirstValues = false;
        if(count($results) > 1) {
            if(!$isSkipConflictingMeasurements) {
                $isGetFirstValues = true;
            }
        } elseif(count($results) == 1) {
            $isGetFirstValues = true;
        }

        if($isGetFirstValues) {
            return $results[0][$label];
        } else {
            return null;
        }
    }

    /**
     * @param string $animalIdAndDate
     * @param boolean $hasValid20WeeksWeightMeasurement
     * @param boolean $isSkipConflictingMeasurements
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function writeMuscleThicknessRowPart($animalIdAndDate, $hasValid20WeeksWeightMeasurement, $isSkipConflictingMeasurements = true)
    {
        $sql = "SELECT t.muscle_thickness FROM measurement m
                  INNER JOIN muscle_thickness t ON m.id = t.id
                WHERE m.animal_id_and_date = '".$animalIdAndDate."'";
        $results = $this->conn->query($sql)->fetchAll();
        $foundMuscleThicknessValue = $this->getMeasurementFromSqlResults($results, $isSkipConflictingMeasurements, 'muscle_thickness');

        if(MeasurementsUtil::isValidMuscleThicknessValue($foundMuscleThicknessValue, $hasValid20WeeksWeightMeasurement)){
            $muscleThicknessValue = Utils::fillZero($foundMuscleThicknessValue,self::MUSCLE_THICKNESS_NULL_FILLER);
            $isEmptyMeasurement = false; //valid values are by default not empty
        } else {
            $muscleThicknessValue = self::MUSCLE_THICKNESS_NULL_FILLER;
            $isEmptyMeasurement = true;
        }

        $result = Utils::addPaddingToStringForColumnFormatCenter($muscleThicknessValue, self::COLUMN_WIDTH_MUSCLE_THICKNESS, self::COLUMN_PADDING_SIZE);

        return [JsonInputConstant::MEASUREMENT_ROW => $result,
            JsonInputConstant::IS_EMPTY_MEASUREMENT => $isEmptyMeasurement];
    }


    /**
     * @param boolean $isSkipConflictingMeasurements
     * @param string $animalIdAndDate
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function writeTailLengthRowPart($animalIdAndDate, $isSkipConflictingMeasurements = true)
    {
        $sql = "SELECT t.length FROM measurement m
                  INNER JOIN tail_length t ON m.id = t.id
                WHERE m.animal_id_and_date = '".$animalIdAndDate."'";
        $results = $this->conn->query($sql)->fetchAll();
        $tailLengthValue = $this->getMeasurementFromSqlResults($results, $isSkipConflictingMeasurements, 'length');
        $isEmptyMeasurement = NullChecker::numberIsNull($tailLengthValue);
        $tailLengthValue = Utils::fillZero($tailLengthValue, self::TAIL_LENGTH_NULL_FILLER);

        $result = Utils::addPaddingToStringForColumnFormatCenter($tailLengthValue, self::COLUMN_WIDTH_TAIL_LENGTH, self::COLUMN_PADDING_SIZE);

        return [JsonInputConstant::MEASUREMENT_ROW => $result,
            JsonInputConstant::IS_EMPTY_MEASUREMENT => $isEmptyMeasurement];
    }


    /**
     * @param string $animalIdAndDate
     * @param boolean $hasValid20WeeksWeightMeasurement
     * @param boolean $isSkipConflictingMeasurements
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function writeBodyFatRowPart($animalIdAndDate, $hasValid20WeeksWeightMeasurement, $isSkipConflictingMeasurements = true)
    {
        $sql = "SELECT fat1, fat2, fat3 FROM measurement m
                INNER JOIN (
                  SELECT body_fat.id as body_fat_id, fat1.fat as fat1, fat2.fat as fat2, fat3.fat as fat3, animal_id 
                  FROM body_fat
                  LEFT JOIN fat1 ON body_fat.fat1_id = fat1.id
                  LEFT JOIN fat2 ON body_fat.fat2_id = fat2.id
                  LEFT JOIN fat3 ON body_fat.fat3_id = fat3.id
                ) b ON m.id = b.body_fat_id
                WHERE m.animal_id_and_date = '".$animalIdAndDate."'";
        $results = $this->conn->query($sql)->fetchAll();

        $isGetFirstValues = false;
        if(count($results) > 1) {
            if(!$isSkipConflictingMeasurements) {
                $isGetFirstValues = true;
            }
        } elseif(count($results) == 1) {
            $isGetFirstValues = true;
        }

        //Default values
        $fat1 = self::FAT_NULL_FILLER;
        $fat2 = self::FAT_NULL_FILLER;
        $fat3 = self::FAT_NULL_FILLER;
        $isEmptyMeasurement = true;

        if($isGetFirstValues) {
            $foundFat1 = floatval($results[0]['fat1']);
            $foundFat2 = floatval($results[0]['fat2']);
            $foundFat3 = floatval($results[0]['fat3']);

            $isValidBodyFatValues = MeasurementsUtil::isValidBodyFatValues($foundFat1, $foundFat2, $foundFat3, $hasValid20WeeksWeightMeasurement);

            if($isValidBodyFatValues) {
                $fat1 = Utils::fillZero($foundFat1, self::FAT_NULL_FILLER);
                $fat2 = Utils::fillZero($foundFat2, self::FAT_NULL_FILLER);
                $fat3 = Utils::fillZero($foundFat3, self::FAT_NULL_FILLER);

                $isEmptyMeasurement = false; //If values are valid they cannot be empty
            }
        }

        $result =
             Utils::addPaddingToStringForColumnFormatCenter($fat1, self::COLUMN_WIDTH_FAT, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fat2, self::COLUMN_WIDTH_FAT, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fat3, self::COLUMN_WIDTH_FAT, self::COLUMN_PADDING_SIZE);

        return [JsonInputConstant::MEASUREMENT_ROW => $result,
            JsonInputConstant::IS_EMPTY_MEASUREMENT => $isEmptyMeasurement];
    }

    
    /**
     * @param string $animalIdAndDateOfMeasurement
     * @param boolean $isSkipConflictingMeasurements
     * @return string
     */
    private function writeDataRecordTestAttributes($animalIdAndDateOfMeasurement, $isSkipConflictingMeasurements = true)
    {
        /* get animal values */
        $codeParts = explode('_', $animalIdAndDateOfMeasurement);
        $animalId = $codeParts[0];
        $measurementDateString = $codeParts[1];

        $rowBaseAndDateOfBirthArray = $this->formatFirstPartDataRecordRowTestAttributesByAnimalDatabaseId($animalId);
        //Includes check for missing Animal
        if($rowBaseAndDateOfBirthArray == null) { return null; }

        $rowBase = $rowBaseAndDateOfBirthArray[self::ROW_DATA];
        $dateOfBirthString = $rowBaseAndDateOfBirthArray[self::DATE_OF_BIRTH];

        $ageGrowthWeightRowData = $this->formatAgeGrowthWeightsRowPart($animalIdAndDateOfMeasurement, $dateOfBirthString, $isSkipConflictingMeasurements);
        $hasValid20WeeksWeightMeasurement = $ageGrowthWeightRowData[JsonInputConstant::IS_VALID_20WEEK_WEIGHT_MEASUREMENT];
        $bodyFatRowData = $this->writeBodyFatRowPart($animalIdAndDateOfMeasurement, $hasValid20WeeksWeightMeasurement, $isSkipConflictingMeasurements);
        $muscleThicknessRowData = $this->writeMuscleThicknessRowPart($animalIdAndDateOfMeasurement, $hasValid20WeeksWeightMeasurement, $isSkipConflictingMeasurements);
        $tailLengthRowData = $this->writeTailLengthRowPart($animalIdAndDateOfMeasurement, $isSkipConflictingMeasurements);
        
        $ageGrowthWeightRowPart = $ageGrowthWeightRowData[JsonInputConstant::MEASUREMENT_ROW];
        $bodyFatRowPart = $bodyFatRowData[JsonInputConstant::MEASUREMENT_ROW];
        $muscleThicknessRowPart = $muscleThicknessRowData[JsonInputConstant::MEASUREMENT_ROW];
        $tailLengthRowPart = $tailLengthRowData[JsonInputConstant::MEASUREMENT_ROW];

        $isAgeGrowthWeightEmpty = $ageGrowthWeightRowData[JsonInputConstant::IS_EMPTY_MEASUREMENT];
        $isBodyFatEmpty = $bodyFatRowData[JsonInputConstant::IS_EMPTY_MEASUREMENT];
        $isMuscleThicknessEmpty = $muscleThicknessRowData[JsonInputConstant::IS_EMPTY_MEASUREMENT];
        $isTailLengthEmpty = $tailLengthRowData[JsonInputConstant::IS_EMPTY_MEASUREMENT];

        $block = self::getMixblupBlockByAnimalId($this->conn, $animalId);

        //Test values might all be empty if all measurements were contradicting duplicates
        $isMeasurementDateMissing = NullChecker::isNull($measurementDateString);
        $measurementDate = self::formatMeasurementDate(new \DateTime($measurementDateString));
        $isAllTestValuesEmpty = $isAgeGrowthWeightEmpty && $isBodyFatEmpty && $isMuscleThicknessEmpty && $isTailLengthEmpty;

        if($isAllTestValuesEmpty || $isMeasurementDateMissing) {
            return null;
        }

        $record =
            $rowBase
            .Utils::addPaddingToStringForColumnFormatCenter($measurementDate, self::COLUMN_WIDTH_DATE, self::COLUMN_PADDING_SIZE)
            .$ageGrowthWeightRowPart
            .$bodyFatRowPart
            .$muscleThicknessRowPart
            .$tailLengthRowPart
            .Utils::addPaddingToStringForColumnFormatSides($block, self::COLUMN_WIDTH_BLOCK, false)
        ;

        return $record;
    }


    /**
     * @param string $animalIdAndDateOfMeasurement
     * @param string $dateOfBirthString
     * @param bool $isSkipConflictingMeasurements
     * @return string
     */
    private function formatAgeGrowthWeightsRowPart($animalIdAndDateOfMeasurement, $dateOfBirthString, $isSkipConflictingMeasurements = true)
    {
        $ageAtMeasurement = $this->getAgeInDays($animalIdAndDateOfMeasurement, $dateOfBirthString);

        //Weights
        $sql = "SELECT w.weight, w.is_birth_weight FROM measurement m
                  INNER JOIN weight w ON m.id = w.id
                WHERE m.animal_id_and_date = '".$animalIdAndDateOfMeasurement."' AND w.is_revoked <> TRUE";
        $results = $this->conn->query($sql)->fetchAll();

        $isGetFirstValues = false;
        if(count($results) > 1) {
            if(!$isSkipConflictingMeasurements) {
                $isGetFirstValues = true;
            }
        } elseif(count($results) == 1) {
            $isGetFirstValues = true;
        }

        if($isGetFirstValues) {

            $foundWeight = Utils::fillZero($results[0]['weight'], self::WEIGHT_NULL_FILLER);
            $weightType = MeasurementsUtil::getWeightType($ageAtMeasurement);
            $isValidMixblupWeight = MeasurementsUtil::isValidMixblupWeight($ageAtMeasurement, floatval($results[0]['weight']));
            $isEmptyMeasurement = NullChecker::numberIsNull($results[0]['weight']);

            /* Set default values */
            $birthWeight = self::WEIGHT_NULL_FILLER;
            $weightAt8Weeks = self::WEIGHT_NULL_FILLER;
            $weightAt20Weeks = self::WEIGHT_NULL_FILLER;
            $growth = self::GROWTH_NULL_FILLER;
            $isValid20WeekWeightMeasurement = false;

            if($isValidMixblupWeight) {
                switch ($weightType) {
                    case WeightType::BIRTH:
                        $birthWeight = $foundWeight;
                        //Don't calculate growth from birthWeight!!!
                        break;

                    case WeightType::EIGHT_WEEKS:
                        $weightAt8Weeks = $foundWeight;
                        break;

                    case WeightType::TWENTY_WEEKS:
                        $weightAt20Weeks = $foundWeight;
                        $growth = BreedValueUtil::getGrowthValue($weightAt20Weeks, $ageAtMeasurement,
                            self::AGE_NULL_FILLER, self::GROWTH_NULL_FILLER, self::WEIGHT_NULL_FILLER, self::DECIMAL_SYMBOL);
                        $isValid20WeekWeightMeasurement = true;
                        break;

                    default:
                        $isEmptyMeasurement = true;
                        break;
                }
            } else {
                $isEmptyMeasurement = true;
            }

        } else {
            $growth = self::GROWTH_NULL_FILLER;
            $birthWeight = self::WEIGHT_NULL_FILLER;
            $weightAt8Weeks = self::WEIGHT_NULL_FILLER;
            $weightAt20Weeks = self::WEIGHT_NULL_FILLER;
            $isEmptyMeasurement = true;
            $isValid20WeekWeightMeasurement = false;
        }

        $result =
             Utils::addPaddingToStringForColumnFormatCenter($ageAtMeasurement, self::COLUMN_WIDTH_AGE, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($growth, self::COLUMN_WIDTH_GROWTH, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($birthWeight, self::COLUMN_WIDTH_WEIGHT, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($weightAt8Weeks, self::COLUMN_WIDTH_WEIGHT, self::COLUMN_PADDING_SIZE)  //8wk weight
            .Utils::addPaddingToStringForColumnFormatCenter($weightAt20Weeks, self::COLUMN_WIDTH_WEIGHT, self::COLUMN_PADDING_SIZE); //20wk weight

        return [JsonInputConstant::MEASUREMENT_ROW => $result,
                JsonInputConstant::IS_EMPTY_MEASUREMENT => $isEmptyMeasurement,
                JsonInputConstant::IS_VALID_20WEEK_WEIGHT_MEASUREMENT => $isValid20WeekWeightMeasurement
        ];
    }


    /**
     * @param Exterior $measurement
     * @return string
     */
    private function formatExteriorMeasurementsRowPart($measurement)
    {
        if($measurement != null && $measurement instanceof Exterior) {

            $kind = $measurement->getKind();
            $skull = $measurement->getSkull();
            $muscularity = $measurement->getMuscularity();
            $proportion = $measurement->getProportion();
            $progress = $measurement->getProgress();
            $exteriorType = $measurement->getExteriorType();
            $legWork = $measurement->getLegWork();
            $fur = $measurement->getFur();
            $generalAppearance = $measurement->getGeneralAppearance();
            $height = $measurement->getHeight();
            $torsoLength = $measurement->getTorsoLength();
            $breastDepth = $measurement->getBreastDepth();
            $markings = $measurement->getMarkings();

            if($skull > 99 || $muscularity > 99 || $proportion > 99 || $progress > 99 || $exteriorType > 99 || $legWork > 99
                || $fur > 99 || $generalAppearance > 99 || $height > 99 || $torsoLength > 99 || $breastDepth > 99 || $markings > 99) {
                return null;
            }


            $kind = Utils::fillZero($kind, self::EXTERIOR_KIND_NULL_FILLER);
            $skull = Utils::fillZero($skull, self::EXTERIOR_NULL_FILLER);
            $muscularity = Utils::fillZero($muscularity, self::EXTERIOR_NULL_FILLER);
            $proportion = Utils::fillZero($proportion, self::EXTERIOR_NULL_FILLER);
            $progress = Utils::fillZero($progress, self::EXTERIOR_NULL_FILLER);
            $exteriorType = Utils::fillZero($exteriorType, self::EXTERIOR_NULL_FILLER);
            $legWork = Utils::fillZero($legWork, self::EXTERIOR_NULL_FILLER);
            $fur = Utils::fillZero($fur, self::EXTERIOR_NULL_FILLER);
            $generalAppearance = Utils::fillZero($generalAppearance, self::EXTERIOR_NULL_FILLER);
            $height = Utils::fillZero($height, self::EXTERIOR_NULL_FILLER);
            $torsoLength = Utils::fillZero($torsoLength, self::EXTERIOR_NULL_FILLER);
            $breastDepth = Utils::fillZero($breastDepth, self::EXTERIOR_NULL_FILLER);
            $markings = Utils::fillZero($markings, self::EXTERIOR_NULL_FILLER);

        } else {
            return null;
        }
        
        $exteriorRowPart =
             Utils::addPaddingToStringForColumnFormatCenter($kind, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($skull, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($muscularity, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($proportion, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($progress, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($exteriorType, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($legWork, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fur, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($generalAppearance, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($height, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($torsoLength, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breastDepth, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($markings, self::COLUMN_WIDTH_EXTERIOR, self::COLUMN_PADDING_SIZE);

        return $exteriorRowPart;
    }


    /**
     * @param int $animalId
     * @return null|string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function formatFirstPartDataRecordRowTestAttributesByAnimalDatabaseId($animalId)
    {
        //If rowBase already exists, retrieve it
        $rowBaseAndDateOfBirthArray = $this->animalRowBases->get($animalId);
        if($rowBaseAndDateOfBirthArray != null) {
            return $rowBaseAndDateOfBirthArray;
        }
        //else create a new one

        $sql = "SELECT
                          a.id, a.gender, a.breed_codes_id, a.breed_code, a.breed_type, a.scrapie_genotype, 
                          a.date_of_birth, a.ubn_of_birth,
                          a.uln_country_code as uln_country_code_a, a.uln_number as uln_number_a,
                          f.uln_country_code as uln_country_code_f, f.uln_number as uln_number_f,
                          m.uln_country_code as uln_country_code_m, m.uln_number as uln_number_m,
                          l.litter_group, l.born_alive_count, l.stillborn_count
                    FROM animal a 
                        LEFT JOIN animal f ON a.parent_father_id = f.id 
                        LEFT JOIN animal m ON a.parent_mother_id = m.id
                        LEFT JOIN litter l ON a.litter_id = l.id
                    WHERE a.id = '".$animalId."'";
        $animalData = $this->conn->query($sql)->fetch();

        $animalUln = self::formatUlnByValue($animalData['uln_country_code_a'], $animalData['uln_number_a'], self::ULN_NULL_FILLER);
        $fatherUln = self::formatUlnByValue($animalData['uln_country_code_f'], $animalData['uln_number_f'], self::ULN_NULL_FILLER);
        $motherUln = self::formatUlnByValue($animalData['uln_country_code_m'], $animalData['uln_number_m'], self::ULN_NULL_FILLER);
        $gender = Utils::fillNullOrEmptyString(self::formatGender($animalData['gender']), self::GENDER_NULL_FILLER);

        // Skip rows with missing uln
        if(NullChecker::isNull($animalData['uln_number_a'])) { return null; }

        $breedCode = Utils::fillNullOrEmptyString($animalData['breed_code'], self::BREED_CODE_NULL_FILLER);
        $breedType = Utils::fillNullOrEmptyString(Translation::getDutchUcFirst($animalData['breed_type']), self::BREED_TYPE_NULL_FILLER);

        $geneDiversity = BreedValueUtil::getHeterosisAndRecombinationBy8Parts($this->em, $animalId, self::HETEROSIS_AND_RECOMBINATION_ROUNDING_ACCURACY);

        $heterosis = Utils::fillNullOrEmptyString($geneDiversity[BreedValueUtil::HETEROSIS], self::HETEROSIS_NULL_FILLER);
        $recombination = Utils::fillNullOrEmptyString($geneDiversity[BreedValueUtil::RECOMBINATION], self::RECOMBINATION_NULL_FILLER);

        $scrapieGenotype = Utils::fillNullOrEmptyString($animalData['scrapie_genotype'], self::SCRAPIE_GENOTYPE_NULL_FILLER);
        $nLing = Utils::fillNullOrEmptyString($animalData['born_alive_count'] + $animalData['stillborn_count'], self::NLING_NULL_FILLER);
        $litterGroup = Utils::fillNullOrEmptyString($animalData['litter_group'], self::LITTER_GROUP_NULL_FILLER);

        $breedCodeValues = $this->getMixBlupTestAttributesBreedCodeTypesById($animalData['breed_codes_id']);
        $dateOfBirthYear = explode('-', $animalData['date_of_birth'])[0];
        $dateOfBirthString = explode(' ', $animalData['date_of_birth'])[0];
        $yearAndUbnOfBirth = self::getYearAndUbnOfBirthStringByValue($dateOfBirthYear, $animalData['ubn_of_birth']);

        //skip rows where to much information is missing
        if(NullChecker::isNull($animalData['gender'])
            && NullChecker::isNull($animalData['uln_number_f'])
            && NullChecker::isNull($animalData['uln_number_m'])
            && (NullChecker::isNull($animalData['date_of_birth']) || NullChecker::isNull($animalData['ubn_of_birth']))) {
            return null;
        }

        $rowBase =
            Utils::addPaddingToStringForColumnFormatSides($animalUln, self::COLUMN_WIDTH_ULN)
            .Utils::addPaddingToStringForColumnFormatCenter($gender, self::COLUMN_WIDTH_GENDER, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($yearAndUbnOfBirth, self::COLUMN_WIDTH_YEAR_AND_UBN, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCode, self::COLUMN_WIDTH_BREED_CODE, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedType, self::COLUMN_WIDTH_BREED_TYPE, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::TE), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::CF), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::NH), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::SW), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::OV), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($heterosis, self::COLUMN_WIDTH_HETEROSIS, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($recombination, self::COLUMN_WIDTH_RECOMBINATION, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($scrapieGenotype, self::COLUMN_WIDTH_GENOTYPE, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($nLing, self::COLUMN_WIDTH_NLING, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($litterGroup, self::COLUMN_WIDTH_LITTER_GROUP, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($motherUln, self::COLUMN_WIDTH_ULN, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fatherUln, self::COLUMN_WIDTH_ULN, self::COLUMN_PADDING_SIZE)
        ;

        $rowBaseAndDateOfBirthArray = [self::ROW_DATA => $rowBase, self::DATE_OF_BIRTH => $dateOfBirthString];

        $this->animalRowBases->set($animalId, $rowBaseAndDateOfBirthArray);

        return $rowBaseAndDateOfBirthArray;
    }


    private function writeDataRecordExteriorAttributes(Exterior $measurement)
    {
        $animal = $measurement->getAnimal();

        if(!$this->isAnimalNotNullAndPrintErrors($animal, $measurement->getId())) { return null; }
        if($this->hasExteriorValuesAboveLimitAndPrintErrors($measurement)) { return null; }
        if(!$animal->isUlnExists()) { return null; }
        
        $rowBase = $this->formatFirstPartDataRecordRowExteriorAttributes($animal);
        $measurementDate = self::formatMeasurementDate($measurement->getMeasurementDate());
        $exteriorRowPart = $this->formatExteriorMeasurementsRowPart($measurement);
        $block = $animal->getMixblupBlock();

        if($exteriorRowPart == null) { return null; }

        $record =
            $rowBase
            .Utils::addPaddingToStringForColumnFormatCenter($measurementDate, self::COLUMN_WIDTH_DATE, self::COLUMN_PADDING_SIZE)
            .$exteriorRowPart
            .Utils::addPaddingToStringForColumnFormatCenter($block, self::COLUMN_WIDTH_BLOCK, self::COLUMN_PADDING_SIZE)
        ;

        return $record;
    }

    private function formatFirstPartDataRecordRowExteriorAttributes(Animal $animal)
    {
        $animalUln = self::formatUln($animal, self::ULN_NULL_FILLER);
        $gender = self::formatGender($animal->getGender());

        $geneDiversity = BreedValueUtil::getHeterosisAndRecombinationBy8Parts($this->em, $animal->getId(), self::HETEROSIS_AND_RECOMBINATION_ROUNDING_ACCURACY);

        $heterosis = Utils::fillNullOrEmptyString($geneDiversity[BreedValueUtil::HETEROSIS], self::HETEROSIS_NULL_FILLER);
        $recombination = Utils::fillNullOrEmptyString($geneDiversity[BreedValueUtil::RECOMBINATION], self::RECOMBINATION_NULL_FILLER);

        $breedCodeValues = $this->getMixBlupExteriorAttributesBreedCodeTypes($animal);
        $yearAndUbnOfBirth = self::getYearAndUbnOfBirthString($animal);

        $record =
            Utils::addPaddingToStringForColumnFormatSides($animalUln, self::COLUMN_WIDTH_ULN)
            .Utils::addPaddingToStringForColumnFormatCenter($gender, self::COLUMN_WIDTH_GENDER, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($yearAndUbnOfBirth, self::COLUMN_WIDTH_YEAR_AND_UBN, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::TE), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::SW), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::BM), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::OV), self::COLUMN_WIDTH_BREED_CODE_PART, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($heterosis, self::COLUMN_WIDTH_HETEROSIS, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($recombination, self::COLUMN_WIDTH_RECOMBINATION, self::COLUMN_PADDING_SIZE)
        ;

        return $record;
    }

    private function writeDataRecordFertility()
    {
        //TODO
    }

    private function formatFirstPartDataRecordRowFertility()
    {
        //TODO
    }


    /**
     * @param \DateTime|null $dateTime
     * @return string|boolean   string when formatting was successful, false if it failed
     */
    public static function formatDateOfBirth($dateTime)
    {
        if($dateTime == null) {
            return self::DATE_OF_BIRTH_NULL_FILLER;
        } else {
            return date_format($dateTime, "Ymd");
        }
    }


    /**
     * @param string $dateTimeString
     * @return string|boolean   string when formatting was successful, false if it failed
     */
    public static function formatDateOfBirthString($dateTimeString)
    {
        if($dateTimeString == null) {
            return self::DATE_OF_BIRTH_NULL_FILLER;
        } else {
            return str_replace('-','', explode(' ', $dateTimeString)[0]);
        }
    }


    /**
     * @param \DateTime|null $dateTime
     * @return string|boolean   string when formatting was successful, false if it failed
     */
    public static function formatMeasurementDate($dateTime)
    {
        if($dateTime == null) {
            return self::MEASUREMENT_DATE_NULL_FILLER;
        } else {
            return date_format($dateTime, "Ymd");
        }
    }


    /**
     * @param $gender
     * @return string|int
     */
    public static function formatGender($gender)
    {
        if($gender == GenderType::M || $gender == GenderType::MALE || $gender == self::RAM) {
            $gender = self::RAM;
        } else if($gender == GenderType::V || $gender == GenderType::FEMALE || $gender = self::EWE) {
            $gender = self::EWE;
        } else {
            $gender = self::GENDER_NULL_FILLER;
        }
        
        return $gender;
    }


    /**
     * @param Animal $animal
     * @param mixed $nullFiller
     * @return string
     */
    public static function formatUln($animal, $nullFiller = '-')
    {
        return self::formatUlnByValue($animal->getUlnCountryCode(), $animal->getUlnNumber(), $nullFiller);
    }


    /**
     * @param string $ulnCountryCode
     * @param string $ulnNumber
     * @param mixed $nullFiller
     * @return string
     */
    public static function formatUlnByValue($ulnCountryCode, $ulnNumber, $nullFiller = '-')
    {
        if(NullChecker::isNotNull($ulnCountryCode) && NullChecker::isNotNull($ulnNumber))
        {
            $result = $ulnCountryCode.$ulnNumber;
        } else {
            $result = $nullFiller;
        }

        return $result;
    }


    /**
     * @param Animal $animal
     * @return ArrayCollection
     */
    public static function formatLitterData($animal)
    {
        $litterData = new ArrayCollection();
        
        $litter = $animal->getLitter();
        if($litter != null) {
            $litterSize = $litter->getSize();
            $litterGroup = $litter->getLitterGroup();
        } else {
            $litterSize = 0;
            $litterGroup = null;
        }

        if($litterSize == null || $litterSize == 0) { $litterSize = self::NLING_NULL_FILLER; }
        if($litterGroup == null || $litterGroup == 0) { $litterGroup = self::LITTER_GROUP_NULL_FILLER; }
        
        $litterData->set(Constant::LITTER_GROUP_NAMESPACE, $litterGroup);
        $litterData->set(Constant::LITTER_SIZE_NAMESPACE, $litterSize);

        return $litterData;
    }


    /**
     * @param Animal $animal
     * @return string
     */
    public static function getUbnFromAnimal(Animal $animal)
    {
        $location = $animal->getLocation();
        if($location == null) {
            $ubn = self::UBN_NULL_FILLER;
        } else {
            $ubn = Utils::fillNullOrEmptyString($location->getUbn(), self::UBN_NULL_FILLER);
        }

        return $ubn;
    }


    /**
     * @param Animal $animal
     * @return int|string
     */
    public static function getYearAndUbnOfBirthString(Animal $animal)
    {
        if($animal->getDateOfBirth() != null) {
            $dateOfBirthYear = date_format($animal->getDateOfBirth(), 'Y');
            return self::getYearAndUbnOfBirthStringByValue($dateOfBirthYear, $animal->getUbnOfBirth());
        }
        return self::YEAR_UBN_NULL_FILLER;
    }


    /**
     * @param string $dateOfBirthYear
     * @param string $ubnOfBirth
     * @return int|string
     */
    public static function getYearAndUbnOfBirthStringByValue($dateOfBirthYear, $ubnOfBirth)
    {
        //Null check
        if($dateOfBirthYear == null || $ubnOfBirth == null || $ubnOfBirth == '') {
            return self::YEAR_UBN_NULL_FILLER;
        }

        return $dateOfBirthYear.'_'.$ubnOfBirth;
    }


    /**
     * Age of Animal on day of measurement
     *
     * @param Animal $animal
     * @param \DateTime $measurementDate
     * @return int|string
     */
    public static function getAgeInDaysOfAnimal(Animal $animal, \DateTime $measurementDate)
    {
        $dateOfBirth = $animal->getDateOfBirth();

        //Null check
        if($dateOfBirth == null) {
            return self::AGE_NULL_FILLER;
        }

        $interval = $measurementDate->diff($dateOfBirth);
        return $interval->days;
    }


    private function getAgeInDays($animalIdAndDate, $dateOfBirthString)
    {
        $measurementDateString = explode('_',$animalIdAndDate)[1];

        if($dateOfBirthString == $measurementDateString) {
            return 0;

        } else {
            $measurementDate = new \DateTime($measurementDateString);
            $dateOfBirth = new \DateTime($dateOfBirthString);
            $dateInterval = $measurementDate->diff($dateOfBirth);
            return $dateInterval->days;
        }
    }


    /**
     * @param float $weightOnThatMoment
     * @param int $ageInDays
     * @return float
     */
    public static function getGrowthValue($weightOnThatMoment, $ageInDays)
    {
        if($weightOnThatMoment == null || $weightOnThatMoment == 0 || $weightOnThatMoment == self::WEIGHT_NULL_FILLER
            || $ageInDays == null || $ageInDays == 0 || $ageInDays == self::AGE_NULL_FILLER) {
            return self::GROWTH_NULL_FILLER;
        } else {
            return number_format($weightOnThatMoment / $ageInDays, 5, ',', '');
        }
    }


    /**
     * @param Animal $animal
     * @return ArrayCollection
     */
    private function getMixBlupTestAttributesBreedCodeTypesById($breed_codes_id)
    {
        if(NullChecker::isNotNull($breed_codes_id)) {
            $sql = "SELECT * FROM breed_codes b
                  INNER JOIN breed_code ON b.id = breed_code.breed_codes_id
                WHERE b.id = ".$breed_codes_id;
            $breedCodeSet = $this->conn->query($sql)->fetchAll();
        } else {
            $breedCodeSet = null;
        }

        //set default values
        $te = 0;
        $cf = 0;
        $nh = 0;
        $sw = 0;
        $ov = 0;

        if($breedCodeSet != null) {

            /** @var BreedCode $breedCode */
            foreach ($breedCodeSet as $breedCode) {

                $breedCodeName = $breedCode['name'];
                $breedCodeValue = $breedCode['value'];

                $name = BreedCodeReformatter::getMixBlupTestAttributesBreedCodeType($breedCodeName);

                switch ($name) {
                    case BreedCodeType::TE:
                        $te += $breedCodeValue;
                        break;
                    case BreedCodeType::CF:
                        $cf += $breedCodeValue;
                        break;
                    case BreedCodeType::NH:
                        $nh += $breedCodeValue;
                        break;
                    case BreedCodeType::SW:
                        $sw += $breedCodeValue;
                        break;
                    default:
                        $ov += $breedCodeValue;
                        break;
                }
            }
        } else {
            //breedCodeSet is missing
            $te = self::BREED_CODE_PARTS_NULL_FILLER;
            $cf = self::BREED_CODE_PARTS_NULL_FILLER;
            $nh = self::BREED_CODE_PARTS_NULL_FILLER;
            $sw = self::BREED_CODE_PARTS_NULL_FILLER;
            $ov = self::BREED_CODE_PARTS_NULL_FILLER;
        }

        $result = new ArrayCollection();
        $result->set(BreedCodeType::TE, $te);
        $result->set(BreedCodeType::CF, $cf);
        $result->set(BreedCodeType::NH, $nh);
        $result->set(BreedCodeType::SW, $sw);
        $result->set(BreedCodeType::OV, $ov);

        return $result;
    }



    /**
     * @param Animal $animal
     * @return ArrayCollection
     */
    private function getMixBlupTestAttributesBreedCodeTypes(Animal $animal)
    {
        //set default values
        $te = 0;
        $cf = 0;
        $nh = 0;
        $sw = 0;
        $ov = 0;

        $breedCodeSet = $animal->getBreedCodes();
        if($breedCodeSet != null) {
            
            /** @var BreedCode $breedCode */
            foreach ($breedCodeSet->getCodes() as $breedCode) {
                $name = BreedCodeReformatter::getMixBlupTestAttributesBreedCodeType($breedCode->getName());

                switch ($name) {
                    case BreedCodeType::TE:
                        $te += $breedCode->getValue();
                        break;
                    case BreedCodeType::CF:
                        $cf += $breedCode->getValue();
                        break;
                    case BreedCodeType::NH:
                        $nh += $breedCode->getValue();
                        break;
                    case BreedCodeType::SW:
                        $sw += $breedCode->getValue();
                        break;
                    default:
                        $ov += $breedCode->getValue();
                        break;
                }
            }
        } else {
            //breedCodeSet is missing
            $te = self::BREED_CODE_PARTS_NULL_FILLER;
            $cf = self::BREED_CODE_PARTS_NULL_FILLER;
            $nh = self::BREED_CODE_PARTS_NULL_FILLER;
            $sw = self::BREED_CODE_PARTS_NULL_FILLER;
            $ov = self::BREED_CODE_PARTS_NULL_FILLER;
        }

        $result = new ArrayCollection();
        $result->set(BreedCodeType::TE, $te);
        $result->set(BreedCodeType::CF, $cf);
        $result->set(BreedCodeType::NH, $nh);
        $result->set(BreedCodeType::SW, $sw);
        $result->set(BreedCodeType::OV, $ov);

        return $result;
    }


    /**
     * @param Animal $animal
     * @return ArrayCollection
     */
    private function getMixBlupExteriorAttributesBreedCodeTypes(Animal $animal)
    {
        //set default values
        $te = 0;
        $sw = 0;
        $bm = 0;
        $ov = 0;

        $breedCodeSet = $animal->getBreedCodes();
        if($breedCodeSet != null) {
            /** @var BreedCode $breedCode */
            foreach ($breedCodeSet->getCodes() as $breedCode) {
                $name = BreedCodeReformatter::getMixBlupExteriorAttributesBreedCodeType($breedCode->getName());

                switch ($name) {
                    case BreedCodeType::TE:
                        $te += $breedCode->getValue();
                        break;
                    case BreedCodeType::SW:
                        $sw += $breedCode->getValue();
                        break;
                    case BreedCodeType::BM:
                        $bm += $breedCode->getValue();
                        break;
                    default:
                        $ov += $breedCode->getValue();
                        break;
                }
            }
        }

        $result = new ArrayCollection();
        $result->set(BreedCodeType::TE, $te);
        $result->set(BreedCodeType::SW, $sw);
        $result->set(BreedCodeType::BM, $bm);
        $result->set(BreedCodeType::OV, $ov);

        return $result;
    }


    /**
     * @param Animal $animal
     * @return ArrayCollection
     */
    private function getMixBlupFertilityBreedCodeTypes(Animal $animal)
    {
        //set default values
        $te = 0;
        $cf = 0;
        $sw = 0;
        $nh = 0;
        $gp = 0;
        $bm = 0;
        $ov = 0;

        $breedCodeSet = $animal->getBreedCodes();
        if($breedCodeSet != null) {
            /** @var BreedCode $breedCode */
            foreach ($breedCodeSet->getCodes() as $breedCode) {
                $name = BreedCodeReformatter::getMixBlupFertilityBreedCodeType($breedCode->getName());

                switch ($name) {
                    case BreedCodeType::TE:
                        $te += $breedCode->getValue();
                        break;
                    case BreedCodeType::CF:
                        $cf += $breedCode->getValue();
                        break;
                    case BreedCodeType::SW:
                        $sw += $breedCode->getValue();
                        break;
                    case BreedCodeType::NH:
                        $nh += $breedCode->getValue();
                        break;
                    case BreedCodeType::GP:
                        $gp += $breedCode->getValue();
                        break;
                    case BreedCodeType::BM:
                        $bm += $breedCode->getValue();
                        break;
                    default:
                        $ov += $breedCode->getValue();
                        break;
                }
            }
        }

        $result = new ArrayCollection();
        $result->set(BreedCodeType::TE, $te);
        $result->set(BreedCodeType::CF, $cf);
        $result->set(BreedCodeType::SW, $sw);
        $result->set(BreedCodeType::NH, $nh);
        $result->set(BreedCodeType::GP, $gp);
        $result->set(BreedCodeType::BM, $bm);
        $result->set(BreedCodeType::OV, $ov);

        return $result;
    }


    /**
     * @param Exterior $exterior
     * @return boolean
     */
    private function hasExteriorValuesAboveLimitAndPrintErrors(Exterior $exterior)
    {
        //This error might occur when a measurement is deleted in the database without removing the Measurement parent row
        if($exterior->hasAnyValuesAbove(self::EXTERIOR_VALUE_LIMIT)) {
            file_put_contents($this->errorsFilePath, 'Measurement with id: '.$exterior->getId()
                .' has values above '.self::EXTERIOR_VALUE_LIMIT."\n", FILE_APPEND);
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param Animal $animal
     * @param string $measurementId
     * @return boolean
     */
    private function isAnimalNotNullAndPrintErrors($animal, $measurementId)
    {
        //This error might occur when a measurement is deleted in the database without removing the Measurement parent row
        if($animal == null) {
            file_put_contents($this->errorsFilePath, 'Measurement with id: '.$measurementId
                .' has no animal.'."\n", FILE_APPEND);
            return false;
        } else {
            return true;
        }
    }


    /**
     * @param Weight $weight
     * @return Weight
     */
    public static function setDateOfBirthForMeasurementDateOfBirthWeight(Weight $weight)
    {
        $animal = $weight->getAnimal();
        if($weight->getIsBirthWeight() && $animal != null) {
            $dateOfBirth = $animal->getDateOfBirth();
            if($dateOfBirth != null && $weight->getMeasurementDate() != $dateOfBirth) {
                $weight->setMeasurementDate($dateOfBirth);
            }
        }

        return $weight;
    }


    /**
     * @param Connection $conn
     * @param $animalId
     * @return int
     */
    public static function getMixblupBlockByAnimalId(Connection $conn, $animalId)
    {
        $sql = "SELECT mixblup_block FROM animal WHERE id = ".$animalId;
        return $conn->query($sql)->fetch()['mixblup_block'];
    }


    public function validateMeasurementData()
    {
        $updateCount = MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false);

        $message = 0 < $updateCount ?
            $updateCount.' animalIdAndDate values updated in measurements'
            : 'All animalIdAndDate values are already up to date!';
        $this->output->writeln($message);
    }
}