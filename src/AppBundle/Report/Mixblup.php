<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BreedCode;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Fat1;
use AppBundle\Entity\Fat2;
use AppBundle\Entity\Fat3;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\MeasurementRepository;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Migration\BreedCodeReformatter;
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

class Mixblup
{
    const BLOCK_NULL_FILLER = 3;
    const ULN_NULL_FILLER = 'N_B';
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
    const COLUMN_PADDING_SIZE = 2;

    const ANIMAL = 'ANIMAL';
    const MEASUREMENT_DATE = 'MEASUREMENT_DATE';
    const BODY_FAT = 'BODY_FAT';
    const MUSCLE_THICKNESS = 'MUSCLE_THICKNESS';
    const TAIL_LENGTH = 'TAIL_LENGTH';
    const WEIGHT = 'WEIGHT';
    const CONTRADICTING_DUPLICATES = 'CONTRADICTING_DUPLICATES';

    //Filename strings
    const TEST_ATTRIBUTES = 'toets_kenmerken';
    const EXTERIOR_ATTRIBUTES = 'exterieur_kenmerken';
    const FERTILITY = 'vruchtbaarheid';
    const ERRORS = 'errors';

    //Versions
    const IS_GROUP_BY_ANIMAL_AND_MEASUREMENT_DATE = true;

    /** @var EntityManager */
    private $em;

    /** @var array */
    private $animals;

    /** @var Collection */
    private $measurements;

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
    private $instructionsFileName;

    /** @var string */
    private $dataFileName;

    /** @var string */
    private $dataFilePathTestAttributes;

    /** @var string */
    private $dataFilePathExteriorAttributes;

    /** @var string */
    private $dataFilePathFertilityAttributes;

    /** @var string */
    private $pedigreeFileName;

    /** @var int */
    private $firstMeasurementYear;

    /** @var int */
    private $lastMeasurementYear;

    /** @var ArrayCollection */
    private $animalRowBases;

    /** @var ArrayCollection $testAttributes */
    private $testAttributes;
    
    /** @var CommandUtil */
    private $cmdUtil;


    /**
     * Mixblup constructor.
     * @param EntityManager $em
     * @param string $outputFolderPath
     * @param string $instructionsFileName
     * @param string $dataFileName
     * @param string $pedigreeFileName
     * @param int $firstMeasurementYear
     * @param int $lastMeasurementYear
     * @param CommandUtil $cmdUtil
     * @param array $animals
     */
    public function __construct(EntityManager $em, $outputFolderPath, $instructionsFileName, $dataFileName, $pedigreeFileName, $firstMeasurementYear, $lastMeasurementYear, $cmdUtil, $animals = null)
    {
        $this->em = $em;
        $this->animalRowBases = new ArrayCollection();
        $this->testAttributes = new ArrayCollection();
        $this->firstMeasurementYear = $firstMeasurementYear;
        $this->lastMeasurementYear = $lastMeasurementYear;
        $this->cmdUtil = $cmdUtil;

        if($animals != null) {
            $this->animals = $animals;
        }

        $this->dataFileName = $dataFileName;
        $this->pedigreeFileName = $pedigreeFileName;
        $this->instructionsFileName = $instructionsFileName;

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
     * @return array
     */
    private function getAnimalsArrayIfNull()
    {
        if($this->animals == null) {

            $sql = "SELECT CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(f.uln_country_code, f.uln_number) as uln_father, CONCAT(m.uln_country_code, m.uln_number) as uln_mother, a.breed_code, a.gender, a.date_of_birth, a.block as block FROM animal a LEFT JOIN animal f ON a.parent_father_id = f.id LEFT JOIN animal m ON a.parent_mother_id = m.id";
            $this->animals = $this->em->getConnection()->query($sql)->fetchAll();
        }
        return $this->animals;
    }


    /**
     * Only retrieve the measurements when they are really needed.
     * @return Collection
     */
    private function getMeasurementsIfNull()
    {
        /** @var MeasurementRepository $measurementRepository */
        $measurementRepository = $this->em->getRepository(Measurement::class);
        $this->measurements = $measurementRepository->getMeasurementsBetweenYears($this->firstMeasurementYear, $this->lastMeasurementYear);
        return $this->measurements;
    }

    /**
     * Only retrieve the exterior measurements when they are really needed.
     * @return Collection
     */
    private function getExteriorMeasurementsIfNull()
    {
        /** @var ExteriorRepository $exteriorRepository */
        $exteriorRepository = $this->em->getRepository(Exterior::class);
        $this->exteriorMeasurements = $exteriorRepository->getExteriorsBetweenYears($this->firstMeasurementYear, $this->lastMeasurementYear);
        return $this->exteriorMeasurements;
    }


    /**
     * @return array
     */
    public function generateInstructionArrayTestAttributes()
    {
        return [
            'TITEL   schapen fokwaarde berekening groei, spierdikte en vetbedekking',
            ' DATAFILE  '.$this->dataFileName.'_'.self::TEST_ATTRIBUTES.'.txt',
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
            ' scrgen     A !missing '.self::SCRAPIE_GENOTYPE_NULL_FILLER.' #scrapiegenotype',
            ' n-ling     I !missing '.self::NLING_NULL_FILLER.' #worp grootte',  //Litter->size()
            ' worpnr     A !missing '.self::LITTER_GROUP_NULL_FILLER.' #worpnummer',  //worpnummer/litterGroup
            ' moeder     A !missing '.self::ULN_NULL_FILLER.' #uln van moeder',  //uln of mother
            ' father     A !missing '.self::ULN_NULL_FILLER.' #uln van vader',  //uln of father
            ' meetdatum  A !missing '.self::MEASUREMENT_DATE_NULL_FILLER, //measurementDate
            ' leeftijd   I !missing '.self::DATE_OF_BIRTH_NULL_FILLER.' #op moment van meting in dagen', //age of animal on measurementDate in days
            ' groei      T !missing '.self::GROWTH_NULL_FILLER.' #gewicht(kg)/leeftijd(dagen) op moment van meting', //growth weight(kg)/age(days) on measurementDate
            ' gebgewicht T !missing '.self::WEIGHT_NULL_FILLER.' #geboortegewicht',   //weight at birth
            ' toetsgewicht T !missing '.self::WEIGHT_NULL_FILLER.' #normale gewichtmeting', //weight during normal measurement
            ' vet1       T !missing '.self::FAT_NULL_FILLER,
            ' vet2       T !missing '.self::FAT_NULL_FILLER,
            ' vet3       T !missing '.self::FAT_NULL_FILLER,
            ' spierdik   T !missing '.self::MUSCLE_THICKNESS_NULL_FILLER.' #spierdikte',
            ' staartlg   T !missing '.self::TAIL_LENGTH_NULL_FILLER.' #staartlengte', //tailLength
            ' block I !BLOCK', //NOTE it is an integer here
            ' ',
            'PEDFILE   '.$this->pedigreeFileName,
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
            ' DATAFILE  '.$this->dataFileName.'_'.self::EXTERIOR_ATTRIBUTES.'.txt',
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
            'PEDFILE   '.$this->pedigreeFileName,
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
            ' DATAFILE  '.$this->dataFileName.'_'.self::FERTILITY.'.txt',
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
            'PEDFILE   '.$this->pedigreeFileName,
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

        foreach ($this->animals as $animalArray) {
            $row = $this->writePedigreeRecord($animalArray);
            file_put_contents($this->pedigreeFilePath, $row."\n", FILE_APPEND);
            $this->cmdUtil->advanceProgressBar(1, 'Generating pedigree file...');
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }
    

    public function generateDataFiles()
    {
        $this->getExteriorMeasurementsIfNull();

        $message = 'Generate exterior measurements...';
        $this->cmdUtil->setStartTimeAndPrintIt($this->exteriorMeasurements->count()+1, 1, $message);

        /** @var Exterior $exteriorMeasurement */
        foreach ($this->exteriorMeasurements as $exteriorMeasurement) {
            $row = $this->writeDataRecordExteriorAttributes($exteriorMeasurement);
            if($row != null) {
                file_put_contents($this->dataFilePathExteriorAttributes, $row."\n", FILE_APPEND);
            }
            $this->cmdUtil->advanceProgressBar(1, $message);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();


        $this->getMeasurementsIfNull();
        $message = 'Generate test measurements...';

        if(self::IS_GROUP_BY_ANIMAL_AND_MEASUREMENT_DATE) {
            $this->writeGroupedDataRecordTestAttributes();
        } else {
            $this->cmdUtil->setStartTimeAndPrintIt($this->measurements->count()+1, 1, $message);
            /** @var Measurement $measurement */
            foreach ($this->measurements as $measurement) {
                $row = $this->writeDataRecordTestAttributes($measurement);
                if($row != null) {
                    file_put_contents($this->dataFilePathTestAttributes, $row."\n", FILE_APPEND);
                    $this->cmdUtil->advanceProgressBar(1, $message);
                }
            }
            $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        }


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

        $breedCode = Utils::fillNullOrEmptyString($animalArray['breed_code'], self::BREED_CODE_NULL_FILLER);
        $gender = Utils::fillNullOrEmptyString(self::formatGender($animalArray['gender']));
        $dateOfBirthString = Utils::fillNullOrEmptyString(self::formatDateOfBirthString($animalArray['date_of_birth']));

        $block = Utils::fillNullOrEmptyString($animalArray['block'], self::BLOCK_NULL_FILLER);

        $record =
        Utils::addPaddingToStringForColumnFormatSides($animalUln, 15)
        .Utils::addPaddingToStringForColumnFormatCenter($fatherUln, 19, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($motherUln, 19, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($block, 10, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($gender, 7, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($dateOfBirthString, 10, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatSides($breedCode, 12, false)
        ;

        return $record;
    }


    private function groupTestMeasurementsByAnimalAndDate()
    {
        $this->getMeasurementsIfNull();

        foreach($this->measurements as $measurement) {
            /** @var Measurement|BodyFat|MuscleThickness|Weight|TailLength|Exterior $measurement */

            if( $measurement instanceof BodyFat || $measurement instanceof Weight ||
                $measurement instanceof MuscleThickness || $measurement instanceof TailLength)
            {
                $animal = $measurement->getAnimal();
                $isAnimalVerified = $this->isAnimalNotNullAndPrintErrors($animal, $measurement->getId());
                if($isAnimalVerified) {

                    $animalAndDateKey = $animal->getId().'_'.$measurement->getMeasurementDate()->getTimestamp();

                    //Null check
                    /** @var ArrayCollection $getAnimalAndDateKey */
                    $getAnimalAndDateKey = $this->testAttributes->get($animalAndDateKey);
                    if($getAnimalAndDateKey == null) {
                        $this->testAttributes->set($animalAndDateKey,new ArrayCollection());
                        $getAnimalAndDateKey = $this->testAttributes->get($animalAndDateKey);
                    }

                    if($measurement instanceof BodyFat) {
                        /** @var BodyFat $measurement */
                        $bodyFatInArray = $getAnimalAndDateKey->get(self::BODY_FAT);
                        if($bodyFatInArray == null) {
                            $getAnimalAndDateKey->set(self::BODY_FAT, $measurement);
                        } else { //There already exists a BodyFat for that data
                            if(!$measurement->isEqualInValues($bodyFatInArray)) {
                                $getAnimalAndDateKey->set(self::BODY_FAT, self::CONTRADICTING_DUPLICATES);
                            }
                            //Else just keep the value or measurement already in that key
                        }
                    }

                    if($measurement instanceof Weight) {
                        /** @var Weight $measurement */
                        $weightInArray = $getAnimalAndDateKey->get(self::WEIGHT);
                        if($weightInArray == null) {
                            $getAnimalAndDateKey->set(self::WEIGHT, $measurement);
                        } else { //There already exists a Weight for that data
                            if(!$measurement->isEqualInValues($weightInArray)) {
                                $getAnimalAndDateKey->set(self::WEIGHT, self::CONTRADICTING_DUPLICATES);
                            }
                            //Else just keep the value or measurement already in that key
                        }
                    }

                    if($measurement instanceof MuscleThickness) {
                        /** @var MuscleThickness $measurement */
                        $muscleThicknessInArray = $getAnimalAndDateKey->get(self::MUSCLE_THICKNESS);
                        if($muscleThicknessInArray == null) {
                            $getAnimalAndDateKey->set(self::MUSCLE_THICKNESS, $measurement);
                        } else { //There already exists a MuscleThickness for that data
                            if(!$measurement->isEqualInValues($muscleThicknessInArray)) {
                                $getAnimalAndDateKey->set(self::MUSCLE_THICKNESS, self::CONTRADICTING_DUPLICATES);
                            }
                            //Else just keep the value or measurement already in that key
                        }
                    }

                    if($measurement instanceof TailLength) {
                        /** @var TailLength $measurement */
                        $tailLengthInArray = $getAnimalAndDateKey->get(self::TAIL_LENGTH);
                        if($tailLengthInArray == null) {
                            $getAnimalAndDateKey->set(self::TAIL_LENGTH, $measurement);
                        } else { //There already exists a TailLength for that data
                            if(!$measurement->isEqualInValues($tailLengthInArray)) {
                                $getAnimalAndDateKey->set(self::TAIL_LENGTH, self::CONTRADICTING_DUPLICATES);
                            }
                            //Else just keep the value or measurement already in that key
                        }
                    }
                }
            }
        }
    }


    private function writeGroupedDataRecordTestAttributes()
    {
        //Create the array grouping measurements by Animal and Date first
        $this->groupTestMeasurementsByAnimalAndDate();

        $message = 'Process measurement group...';
        $this->cmdUtil->setStartTimeAndPrintIt($this->testAttributes->count()+1, 1, $message);

        foreach ($this->testAttributes as $measurementGroup) {

            //Set default values
            $animal = null;
            $measurementDate = null;
            $tailLengthRowPart = $this->formatTailLengthMeasurementsRowPart(null);
            $ageGrowthWeightRowPart = $this->formatAgeGrowthWeightMeasurementsRowPart(null);
            $bodyFatRowPart = $this->formatBodyFatMeasurementsRowPart(null);
            $muscleThicknessRowPart = $this->formatMuscleThicknessMeasurementsRowPart(null);

            foreach ($measurementGroup as $measurement) {

                /* fill measurement data */
                if($measurement instanceof BodyFat) { //Fat1, Fat2 & Fat3 are included here
                    /** @var BodyFat $measurement */
                    if($animal == null) { $animal = $measurement->getAnimal(); }
                    if($measurementDate == null) { $measurementDate = $measurement->getMeasurementDate(); }
                    $bodyFatRowPart = $this->formatBodyFatMeasurementsRowPart($measurement);

                } else if ($measurement instanceof MuscleThickness) {
                    /** @var MuscleThickness $measurement */
                    if($animal == null) { $animal = $measurement->getAnimal(); }
                    if($measurementDate == null) { $measurementDate = $measurement->getMeasurementDate(); }
                    $muscleThicknessRowPart = $this->formatMuscleThicknessMeasurementsRowPart($measurement);

                } else if ($measurement instanceof TailLength) {
                    /** @var TailLength
                     * $measurement */
                    if($animal == null) { $animal = $measurement->getAnimal(); }
                    if($measurementDate == null) { $measurementDate = $measurement->getMeasurementDate(); }
                    $tailLengthRowPart = $this->formatTailLengthMeasurementsRowPart($measurement);

                } else if ($measurement instanceof Weight) {
                    /** @var Weight $measurement */
                    if($animal == null) { $animal = $measurement->getAnimal(); }
                    if($measurementDate == null) { $measurementDate = $measurement->getMeasurementDate(); }
                    $ageGrowthWeightRowPart = $this->formatAgeGrowthWeightMeasurementsRowPart($measurement);

                } else {
                    //skip this measurement
                }
            }

            //Test values might all be empty if all measurements were contradicting duplicates
            $isAllTestValuesEmpty = $animal == null || $measurementDate == null;

            if(!$isAllTestValuesEmpty) {
                $block = $animal->getMixblupBlock();
                $rowBase = $this->formatFirstPartDataRecordRowTestAttributes($animal);
                $measurementDate = self::formatMeasurementDate($measurementDate);

                $record =
                    $rowBase
                    .Utils::addPaddingToStringForColumnFormatCenter($measurementDate, 10, self::COLUMN_PADDING_SIZE)
                    .$ageGrowthWeightRowPart
                    .$bodyFatRowPart
                    .$muscleThicknessRowPart
                    .$tailLengthRowPart
                    .Utils::addPaddingToStringForColumnFormatCenter($block, 10, self::COLUMN_PADDING_SIZE)
                ;

                file_put_contents($this->dataFilePathTestAttributes, $record."\n", FILE_APPEND);
            }
            //else if no valid test data is available, don't write anything to the file

            $this->cmdUtil->advanceProgressBar(1, $message);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }

    /**
     * @param Measurement $measurement
     * @return string
     */
    private function writeDataRecordTestAttributes(Measurement $measurement)
    {
        //Set default values
        $tailLengthRowPart = $this->formatTailLengthMeasurementsRowPart(null);
        $ageGrowthWeightRowPart = $this->formatAgeGrowthWeightMeasurementsRowPart(null);
        $bodyFatRowPart = $this->formatBodyFatMeasurementsRowPart(null);
        $muscleThicknessRowPart = $this->formatMuscleThicknessMeasurementsRowPart(null);

        /* fill measurement data */
        if($measurement instanceof BodyFat) { //Fat1, Fat2 & Fat3 are included here
            /** @var BodyFat $measurement */
            $animal = $measurement->getAnimal();
            $bodyFatRowPart = $this->formatBodyFatMeasurementsRowPart($measurement);

        } else if ($measurement instanceof MuscleThickness) {
            /** @var MuscleThickness $measurement */
            $animal = $measurement->getAnimal();
            $muscleThicknessRowPart = $this->formatMuscleThicknessMeasurementsRowPart($measurement);

        } else if ($measurement instanceof TailLength) {
            /** @var TailLength
             * $measurement */
            $animal = $measurement->getAnimal();
            $tailLengthRowPart = $this->formatTailLengthMeasurementsRowPart($measurement);

        } else if ($measurement instanceof Weight) {
            /** @var Weight $measurement */
            //skip revoked weight measurements
            if($measurement->getIsRevoked()) {
                return null;
            }
            $animal = $measurement->getAnimal();
            $ageGrowthWeightRowPart = $this->formatAgeGrowthWeightMeasurementsRowPart($measurement);

        } else if ($measurement instanceof Exterior) {
            //Skip exteriorMeasurements for the TestAttributes datafile
            return null; //do nothing

        } else {
            return null; //do nothing
        }

        //Null check animal in measurement.
        if(!$this->isAnimalNotNullAndPrintErrors($animal, $measurement->getId())) { return null; }

        $ubn = self::getUbnFromAnimal($animal);
        $rowBase = $this->formatFirstPartDataRecordRowTestAttributes($animal);
        $measurementDate = self::formatMeasurementDate($measurement->getMeasurementDate());

        $record =
            $rowBase
            .Utils::addPaddingToStringForColumnFormatCenter($measurementDate, 10, self::COLUMN_PADDING_SIZE)
            .$ageGrowthWeightRowPart
            .$bodyFatRowPart
            .$muscleThicknessRowPart
            .$tailLengthRowPart
            .Utils::addPaddingToStringForColumnFormatCenter($ubn, 10, self::COLUMN_PADDING_SIZE)
        ;


        return $record;
    }


    /**
     * @param BodyFat $measurement
     * @return string
     */
    private function formatBodyFatMeasurementsRowPart($measurement)
    {
        if($measurement != null && $measurement instanceof BodyFat) {
            /** @var Fat1 $fat1 */
            $fat1 = $measurement->getFat1();
            /** @var Fat2 $fat2 */
            $fat2 = $measurement->getFat2();
            /** @var Fat3 $fat3 */
            $fat3 = $measurement->getFat3();

            if($fat1 != null) {
                $fat1 = $fat1->getFat();
                Utils::fillZero($fat1, self::FAT_NULL_FILLER);
            } else {
                $fat1 = self::FAT_NULL_FILLER;
            }

            if($fat2 != null) {
                $fat2 = $fat2->getFat();
                Utils::fillZero($fat2, self::FAT_NULL_FILLER);
            } else {
                $fat2 = self::FAT_NULL_FILLER;
            }

            if($fat3 != null) {
                $fat3 = $fat3->getFat();
                Utils::fillZero($fat3, self::FAT_NULL_FILLER);
            } else {
                $fat3 = self::FAT_NULL_FILLER;
            }
        } else {
            $fat1 = self::FAT_NULL_FILLER; $fat2 = self::FAT_NULL_FILLER; $fat3 = self::FAT_NULL_FILLER;
        }

        $bodyFatRowPart =
            Utils::addPaddingToStringForColumnFormatCenter($fat1, 7, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fat2, 7, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fat3, 7, self::COLUMN_PADDING_SIZE);

        return $bodyFatRowPart;
    }


    /**
     * @param MuscleThickness $measurement
     * @return string
     */
    private function formatMuscleThicknessMeasurementsRowPart($measurement)
    {
        if($measurement != null && $measurement instanceof MuscleThickness) {
            $muscleThickness = Utils::fillZero($measurement->getMuscleThickness(),self::MUSCLE_THICKNESS_NULL_FILLER);
        } else {
            $muscleThickness = self::MUSCLE_THICKNESS_NULL_FILLER;
        }

        $muscleThicknessRowPart = Utils::addPaddingToStringForColumnFormatCenter($muscleThickness, 6, self::COLUMN_PADDING_SIZE);

        return $muscleThicknessRowPart;
    }


    /**
     * @param TailLength $measurement
     * @return string
     */
    private function formatTailLengthMeasurementsRowPart($measurement)
    {
        if($measurement != null && $measurement instanceof TailLength) {
            $tailLength = Utils::fillZero($measurement->getLength(), self::TAIL_LENGTH_NULL_FILLER);
        } else {
            $tailLength = self::TAIL_LENGTH_NULL_FILLER;
        }

        $tailLengthRowPart = Utils::addPaddingToStringForColumnFormatCenter($tailLength, 8, self::COLUMN_PADDING_SIZE);

        return $tailLengthRowPart;
    }


    /**
     * @param Weight $measurement
     * @return string
     */
    private function formatAgeGrowthWeightMeasurementsRowPart($measurement)
    {
        $weight = self::WEIGHT_NULL_FILLER;
        $birthWeight = self::WEIGHT_NULL_FILLER;
        $ageAtMeasurement = self::AGE_NULL_FILLER;

        if($measurement != null && $measurement instanceof Weight) {
            if($measurement->getIsBirthWeight()){
                //First check and fix birthWeight measurementDates
                $measurement = self::setDateOfBirthForMeasurementDateOfBirthWeight($measurement);
                $this->em->persist($measurement);
                $this->em->flush();

                $birthWeight = Utils::fillZero($measurement->getWeight(), self::WEIGHT_NULL_FILLER);
            } else {
                $weight = Utils::fillZero($measurement->getWeight(), self::WEIGHT_NULL_FILLER);
            }

            $animal = $measurement->getAnimal();
            if($this->isAnimalNotNullAndPrintErrors($animal, $measurement->getId())) {
                $ageAtMeasurement = self::getAgeInDays($animal, $measurement->getMeasurementDate());
            } else {
                $ageAtMeasurement = self::AGE_NULL_FILLER;
            }
        }

        if($weight != self::WEIGHT_NULL_FILLER) {
            //Don't calculate growth from birthWeight
            $growth = BreedValueUtil::getGrowthValue($weight, $ageAtMeasurement,
                self::AGE_NULL_FILLER, self::GROWTH_NULL_FILLER, self::WEIGHT_NULL_FILLER);
        } else {
            $growth = self::GROWTH_NULL_FILLER;
        }


        $growthAgeWeightRowPart =
              Utils::addPaddingToStringForColumnFormatCenter($ageAtMeasurement, 7, self::COLUMN_PADDING_SIZE)
             .Utils::addPaddingToStringForColumnFormatCenter($growth, 9, self::COLUMN_PADDING_SIZE)
             .Utils::addPaddingToStringForColumnFormatCenter($birthWeight, 8, self::COLUMN_PADDING_SIZE)
             .Utils::addPaddingToStringForColumnFormatCenter($weight, 8, self::COLUMN_PADDING_SIZE);

        return $growthAgeWeightRowPart;
    }


    /**
     * @param Exterior $measurement
     * @return string
     */
    private function formatExteriorMeasurementsRowPart($measurement)
    {
        if($measurement != null && $measurement instanceof Exterior) {
            $kind = Utils::fillZero($measurement->getKind(), self::EXTERIOR_KIND_NULL_FILLER);
            $skull = Utils::fillZero($measurement->getSkull(), self::EXTERIOR_NULL_FILLER);
            $muscularity = Utils::fillZero($measurement->getMuscularity(), self::EXTERIOR_NULL_FILLER);
            $proportion = Utils::fillZero($measurement->getProportion(), self::EXTERIOR_NULL_FILLER);
            $progress = Utils::fillZero($measurement->getProgress(), self::EXTERIOR_NULL_FILLER);
            $exteriorType = Utils::fillZero($measurement->getExteriorType(), self::EXTERIOR_NULL_FILLER);
            $legWork = Utils::fillZero($measurement->getLegWork(), self::EXTERIOR_NULL_FILLER);
            $fur = Utils::fillZero($measurement->getFur(), self::EXTERIOR_NULL_FILLER);
            $generalAppearance = Utils::fillZero($measurement->getGeneralAppearence(), self::EXTERIOR_NULL_FILLER);
            $height = Utils::fillZero($measurement->getHeight(), self::EXTERIOR_NULL_FILLER);
            $torsoLength = Utils::fillZero($measurement->getTorsoLength(), self::EXTERIOR_NULL_FILLER);
            $breastDepth = Utils::fillZero($measurement->getBreastDepth(), self::EXTERIOR_NULL_FILLER);
            $markings = Utils::fillZero($measurement->getMarkings(), self::EXTERIOR_NULL_FILLER);

        } else {
            $kind = self::EXTERIOR_KIND_NULL_FILLER;
            $skull = self::EXTERIOR_NULL_FILLER;
            $muscularity = self::EXTERIOR_NULL_FILLER;
            $proportion = self::EXTERIOR_NULL_FILLER;
            $progress = self::EXTERIOR_NULL_FILLER;
            $exteriorType = self::EXTERIOR_NULL_FILLER;
            $legWork = self::EXTERIOR_NULL_FILLER;
            $fur = self::EXTERIOR_NULL_FILLER;
            $generalAppearance = self::EXTERIOR_NULL_FILLER;
            $height = self::EXTERIOR_NULL_FILLER;
            $torsoLength = self::EXTERIOR_NULL_FILLER;
            $breastDepth = self::EXTERIOR_NULL_FILLER;
            $markings = self::EXTERIOR_NULL_FILLER;
        }

        $exteriorRowPart =
             Utils::addPaddingToStringForColumnFormatCenter($kind, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($skull, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($muscularity, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($proportion, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($progress, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($exteriorType, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($legWork, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fur, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($generalAppearance, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($height, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($torsoLength, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breastDepth, 4, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($markings, 4, self::COLUMN_PADDING_SIZE);

        return $exteriorRowPart;
    }

    /**
     * @param Animal $animal
     * @return string
     */
    private function formatFirstPartDataRecordRowTestAttributes(Animal $animal)
    {
        //If rowBase already exists, retrieve it
        $record = $this->animalRowBases->get($animal->getId());
        if($record != null) {
            return $record;
        }
        //else create a new one

        $animalUln = self::formatUln($animal, self::ULN_NULL_FILLER);
        $parents = CommandUtil::getParentUlnsFromParentsArray($animal->getParents(), self::ULN_NULL_FILLER);
        $motherUln = $parents->get(Constant::MOTHER_NAMESPACE);
        $fatherUln = $parents->get(Constant::FATHER_NAMESPACE);
        $gender = self::formatGender($animal->getGender());

        $breedCode = Utils::fillNullOrEmptyString($animal->getBreedCode(), self::BREED_CODE_NULL_FILLER);
        $breedType = Utils::fillNullOrEmptyString(Translation::translateBreedType($animal->getBreedType()), self::BREED_TYPE_NULL_FILLER);
        $scrapieGenotype = Utils::fillNullOrEmptyString($animal->getScrapieGenotype(), self::SCRAPIE_GENOTYPE_NULL_FILLER);

        $litterData = self::formatLitterData($animal);
        $nLing = $litterData->get(Constant::LITTER_SIZE_NAMESPACE);
        $litterGroup = $litterData->get(Constant::LITTER_GROUP_NAMESPACE);

        $breedCodeValues = $this->getMixBlupTestAttributesBreedCodeTypes($animal);
        $yearAndUbnOfBirth = self::getYearAndUbnOfBirthString($animal);

        $record =
            Utils::addPaddingToStringForColumnFormatSides($animalUln, 15)
            .Utils::addPaddingToStringForColumnFormatCenter($gender, 5, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($yearAndUbnOfBirth, 16, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCode, 10, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedType, 16, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::TE), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::CF), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::NH), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::SW), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::OV), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($scrapieGenotype, 9, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($nLing, 5, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($litterGroup, 9, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($motherUln, 16, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fatherUln, 16, self::COLUMN_PADDING_SIZE)
            ;

        $this->animalRowBases->set($animal->getId(), $record);

        return $record;
    }


    private function writeDataRecordExteriorAttributes(Exterior $measurement)
    {
        $animal = $measurement->getAnimal();

        if(!$this->isAnimalNotNullAndPrintErrors($animal, $measurement->getId())) { return null; }

        $rowBase = $this->formatFirstPartDataRecordRowExteriorAttributes($animal);
        $measurementDate = self::formatMeasurementDate($measurement->getMeasurementDate());
        $exteriorRowPart = $this->formatExteriorMeasurementsRowPart($measurement);
        $block = $animal->getMixblupBlock();

        $record =
            $rowBase
            .Utils::addPaddingToStringForColumnFormatCenter($measurementDate, 10, self::COLUMN_PADDING_SIZE)
            .$exteriorRowPart
            .Utils::addPaddingToStringForColumnFormatCenter($block, 10, self::COLUMN_PADDING_SIZE)
        ;

        return $record;
    }

    private function formatFirstPartDataRecordRowExteriorAttributes(Animal $animal)
    {
        $animalUln = self::formatUln($animal, self::ULN_NULL_FILLER);
        $gender = self::formatGender($animal->getGender());
        $heterosis = Utils::fillNullOrEmptyString($animal->getHeterosis(), self::HETEROSIS_NULL_FILLER);
        $recombination = Utils::fillNullOrEmptyString($animal->getRecombination(), self::RECOMBINATION_NULL_FILLER);

        $breedCodeValues = $this->getMixBlupExteriorAttributesBreedCodeTypes($animal);
        $yearAndUbnOfBirth = self::getYearAndUbnOfBirthString($animal);

        $record =
            Utils::addPaddingToStringForColumnFormatSides($animalUln, 15)
            .Utils::addPaddingToStringForColumnFormatCenter($gender, 5, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($yearAndUbnOfBirth, 16, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::TE), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::SW), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::BM), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCodeValues->get(BreedCodeType::OV), 3, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($heterosis, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($recombination, 6, self::COLUMN_PADDING_SIZE)
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
        if($gender == GenderType::M || $gender == GenderType::MALE) {
            $gender = self::RAM;
        } else if($gender == GenderType::V || $gender == GenderType::FEMALE) {
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
        if($animal->getUlnCountryCode() != null && $animal->getUlnNumber() != null)
        {
            $result = $animal->getUlnCountryCode().$animal->getUlnNumber();
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
        $dateOfBirth = $animal->getDateOfBirth();
        $ubnOfBirth = $animal->getUbnOfBirth();

        //Null check
        if($dateOfBirth == null || $ubnOfBirth == null || $ubnOfBirth == '') {
            return self::YEAR_UBN_NULL_FILLER;
        }

        return date_format($dateOfBirth, 'Y').'_'.$ubnOfBirth;
    }


    /**
     * Age of Animal on day of measurement
     *
     * @param Animal $animal
     * @param \DateTime $measurementDate
     * @return int|string
     */
    public static function getAgeInDays(Animal $animal, \DateTime $measurementDate)
    {
        $dateOfBirth = $animal->getDateOfBirth();

        //Null check
        if($dateOfBirth == null) {
            return self::AGE_NULL_FILLER;
        }

        $interval = $measurementDate->diff($dateOfBirth);
        return $interval->days;
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
}