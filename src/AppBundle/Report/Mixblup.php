<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\Fat1;
use AppBundle\Entity\Fat2;
use AppBundle\Entity\Fat3;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\MeasurementRepository;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

class Mixblup
{
    const PARENT_NULL_FILLER = 0;
    const BREED_CODE_NULL_FILLER = 0;
    const BREED_TYPE_NULL_FILLER = 0;
    const GENDER_NULL_FILLER = 0;
    const SCRAPIE_GENOTYPE_NULL_FILLER = 0;
    const NLING_NULL_FILLER = 0;
    const LITTER_GROUP_NULL_FILLER = 0;
    const DATE_OF_BIRTH_NULL_FILLER = 0;
    const MEASUREMENT_DATE_NULL_FILLER = 0;
    const FAT_NULL_FILLER = 0;
    const MUSCLE_THICKNESS_NULL_FILLER = 0;
    const TAIL_LENGTH_NULL_FILLER = 0;
    const WEIGHT_NULL_FILLER = 0;
    const PERFORMANCE_NULL_FILLER = 0;
    const EXTERIOR_NULL_FILLER = 0;
    const RAM = 'ram';
    const EWE = 'ooi';
    const NEUTER = 0;
    const COLUMN_PADDING_SIZE = 1;

    /** @var EntityManager */
    private $em;

    /** @var array */
    private $animals;

    /** @var Collection */
    private $measurements;

    /** @var string */
    private $instructionsFilePath;

    /** @var string */
    private $dataFilePath;

    /** @var string */
    private $pedigreeFilePath;

    /** @var string */
    private $instructionsFileName;

    /** @var string */
    private $dataFileName;

    /** @var string */
    private $pedigreeFileName;

    /** @var int */
    private $firstMeasurementYear;

    /** @var int */
    private $lastMeasurementYear;

    /**
     * Mixblup constructor.
     * @param EntityManager $em
     * @param string $outputFolderPath
     * @param string $instructionsFileName
     * @param string $dataFileName
     * @param string $pedigreeFileName
     * @param int $firstMeasurementYear
     * @param int $lastMeasurementYear
     * @param array $animals
     */
    public function __construct(EntityManager $em, $outputFolderPath, $instructionsFileName, $dataFileName, $pedigreeFileName, $firstMeasurementYear, $lastMeasurementYear, $animals = null)
    {
        $this->em = $em;
        $this->firstMeasurementYear = $firstMeasurementYear;
        $this->lastMeasurementYear = $lastMeasurementYear;

        if($animals != null) {
            $this->animals = $animals;
        }

        $this->dataFileName = $dataFileName;
        $this->pedigreeFileName = $pedigreeFileName;
        $this->instructionsFileName = $instructionsFileName;

        if(substr($outputFolderPath, -1) != '/') {
            $this->dataFilePath = $outputFolderPath.'/'.$dataFileName;
            $this->pedigreeFilePath = $outputFolderPath.'/'.$pedigreeFileName;
            $this->instructionsFilePath = $outputFolderPath.'/'.$instructionsFileName;
        } else {
            $this->dataFilePath = $outputFolderPath.$dataFileName;
            $this->pedigreeFilePath = $outputFolderPath.$pedigreeFileName;
            $this->instructionsFilePath = $outputFolderPath.$instructionsFileName;
        }

    }


    /**
     * Only retrieve the animals when they are really needed.
     * @return array
     */
    private function getAnimalsIfNull()
    {
        if($this->animals == null) {
            $this->animals = $this->em->getRepository(Animal::class)->findAll();
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
     * @return array
     */
    public function generateInstructionArray()
    {
        return [
            'TITEL   schapen fokwaarde berekening groei, spierdikte en vetbedekking',
            ' DATAFILE  '.$this->dataFileName,
            ' animal     A #uln',  //uln
            ' gender     A',  //ram/ooi/0
            ' rascode    A',  //breedCode
            ' rasstatus  A',  //breedType
            ' scrgen     A #scrapiegenotype',
            ' n-ling     I #worp grootte',  //Litter->size()
            ' worpnr     A #worpnummer',  //worpnummer/litterGroup
            ' moeder     A #uln van moeder',  //uln of mother
            ' father     A #uln van vader',  //uln of father
            ' meetdatum  A', //measurementDate
            ' vet1       T',
            ' vet2       T',
            ' vet3       T',
            ' spierdik   T #spierdikte',
            ' staartlg   T #staartlengte', //tailLength
            ' gebgewicht T #geboortegewicht',   //weight at birth
            ' toetsgewicht T #normale gewichtmeting', //weight during normal measurement
            ' KOP T #kop', //skull
            ' BES T #bespiering', //muscularity
            ' EVE T #evenredigheid', //proportion
            ' TYP T #type', //(exterior)type
            ' BEE T #beenwerk', //legWork
            ' VAC T #vacht', //fur
            ' ALG T #algemene voorkoming', //general appearance
            ' SHT T #schofthoogte', //height
            ' LGT T #lengte', //length
            ' BDP T #borstdiepte', //breast depth
            ' KEN T #kenmerken', //markings
            ' ',
            'PEDFILE   '.$this->pedigreeFileName,
            ' animal    A #uln',
            ' sire      A #uln van vader',
            ' dam       A #uln van moeder',
            ' gender    A',
            ' gebjaar   A #geboortedatum',
            ' rascode   A',
            ' ',
            'PARFILE  *insert par file reference here*',
            ' ',
            'MODEL    *insert model settings here*',
            ' ',
            'SOLVING  *insert solve settings here*'

        ];
    }


    /**
     * @return string
     */
    public function generateInstructionFile()
    {
        foreach($this->generateInstructionArray() as $row) {
            file_put_contents($this->instructionsFilePath, $row."\n", FILE_APPEND);
        }
        return $this->instructionsFilePath;
    }
    

    /**
     * @return array
     */
    public function generatePedigreeArray()
    {
        $this->getAnimalsIfNull();
        $result = array();
        
        foreach ($this->animals as $animal) {
            $result[] = $this->writePedigreeRecord($animal);
        }
        
        return $result;
    }

    
    /**
     * @return string
     */
    public function generatePedigreeFile()
    {
        $this->getAnimalsIfNull();

        foreach ($this->animals as $animal) {
            $row = $this->writePedigreeRecord($animal);
            file_put_contents($this->pedigreeFilePath, $row."\n", FILE_APPEND);
        }

        return $this->pedigreeFilePath;
    }


    /**
     * @return array
     */
    public function generateDataArray()
    {
        $result = array();

        /** @var Measurement $measurement */
        foreach ($this->measurements as $measurement) {
            $result[] = $this->writeDataRecord($measurement);
        }

        return $result;
    }


    /**
     * @return string
     */
    public function generateDataFile()
    {
        $this->getMeasurementsIfNull();

        /** @var Measurement $measurement */
        foreach ($this->measurements as $measurement) {
            $row = $this->writeDataRecord($measurement);
            file_put_contents($this->dataFilePath, $row."\n", FILE_APPEND);
        }

        return $this->dataFilePath;
    }


    /**
     * @param Animal $animal
     * @return string
     */
    private function writePedigreeRecord(Animal $animal)
    {
        $animalUln = self::formatUln($animal);
        $parents = CommandUtil::getParentUlnsFromParentsArray($animal->getParents(), self::PARENT_NULL_FILLER);
        $motherUln = $parents->get(Constant::MOTHER_NAMESPACE);
        $fatherUln = $parents->get(Constant::FATHER_NAMESPACE);

        $breedCode = Utils::fillNullOrEmptyString($animal->getBreedCode(), self::BREED_TYPE_NULL_FILLER);
        $gender = self::formatGender($animal->getGender());
        $dateOfBirthString = self::formatDateOfBirth($animal->getDateOfBirth());

        $record =
        Utils::addPaddingToStringForColumnFormatSides($animalUln, 15)
        .Utils::addPaddingToStringForColumnFormatCenter($fatherUln, 19, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($motherUln, 19, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($gender, 7, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatCenter($dateOfBirthString, 10, self::COLUMN_PADDING_SIZE)
        .Utils::addPaddingToStringForColumnFormatSides($breedCode, 12)
        ;

        return $record;
    }

    /**
     * @param Measurement $measurement
     * @return string
     */
    private function writeDataRecord(Measurement $measurement)
    {
        //Set default values
        $fat1 = self::FAT_NULL_FILLER; $fat2 = self::FAT_NULL_FILLER; $fat3 = self::FAT_NULL_FILLER;
        $muscleThickness = self::MUSCLE_THICKNESS_NULL_FILLER;
        $tailLength = self::TAIL_LENGTH_NULL_FILLER;
        $weight = self::WEIGHT_NULL_FILLER;
        $birthWeight = self::WEIGHT_NULL_FILLER;

        $skull = self::EXTERIOR_NULL_FILLER;
        $muscularity = self::EXTERIOR_NULL_FILLER;
        $proportion = self::EXTERIOR_NULL_FILLER;
        $exteriorType = self::EXTERIOR_NULL_FILLER;
        $legWork = self::EXTERIOR_NULL_FILLER;
        $fur = self::EXTERIOR_NULL_FILLER;
        $generalAppearance = self::EXTERIOR_NULL_FILLER;
        $height = self::EXTERIOR_NULL_FILLER;
        $torsoLength = self::EXTERIOR_NULL_FILLER;
        $breastDepth = self::EXTERIOR_NULL_FILLER;
        $markings = self::EXTERIOR_NULL_FILLER;

        /* fill measurement data */
        if($measurement instanceof BodyFat) { //Fat1, Fat2 & Fat3 are included here
            /** @var BodyFat $measurement */
            $animal = $measurement->getAnimal();
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

        } else if ($measurement instanceof MuscleThickness) {
            /** @var MuscleThickness $measurement */
            $animal = $measurement->getAnimal();
            $muscleThickness = Utils::fillZero($measurement->getMuscleThickness(),self::MUSCLE_THICKNESS_NULL_FILLER);

        } else if ($measurement instanceof TailLength) {
            /** @var TailLength
             * $measurement */
            $animal = $measurement->getAnimal();
            $tailLength = Utils::fillZero($measurement->getLength(), self::TAIL_LENGTH_NULL_FILLER);

        } else if ($measurement instanceof Weight) {
            /** @var Weight $measurement */
            $animal = $measurement->getAnimal();
            if($measurement->getIsBirthWeight()){
                $birthWeight = Utils::fillZero($measurement->getWeight(), self::WEIGHT_NULL_FILLER);
            } else {
                $weight = Utils::fillZero($measurement->getWeight(), self::WEIGHT_NULL_FILLER);
            }

        } else if ($measurement instanceof Exterior) {
            /** @var Exterior $measurement */
            $animal = $measurement->getAnimal();

            $skull = Utils::fillZero($measurement->getSkull(), self::EXTERIOR_NULL_FILLER);
            $muscularity = Utils::fillZero($measurement->getMuscularity(), self::EXTERIOR_NULL_FILLER);
            $proportion = Utils::fillZero($measurement->getProportion(), self::EXTERIOR_NULL_FILLER);
            $exteriorType = Utils::fillZero($measurement->getExteriorType(), self::EXTERIOR_NULL_FILLER);
            $legWork = Utils::fillZero($measurement->getLegWork(), self::EXTERIOR_NULL_FILLER);
            $fur = Utils::fillZero($measurement->getFur(), self::EXTERIOR_NULL_FILLER);
            $generalAppearance = Utils::fillZero($measurement->getGeneralAppearence(), self::EXTERIOR_NULL_FILLER);
            $height = Utils::fillZero($measurement->getHeight(), self::EXTERIOR_NULL_FILLER);
            $torsoLength = Utils::fillZero($measurement->getTorsoLength(), self::EXTERIOR_NULL_FILLER);
            $breastDepth = Utils::fillZero($measurement->getBreastDepth(), self::EXTERIOR_NULL_FILLER);
            $markings = Utils::fillZero($measurement->getMarkings(), self::EXTERIOR_NULL_FILLER);

        } else {
            return null; //do nothing
        }

        $rowBase = $this->formatFirstPartDataRecordRow($animal);
        $measurementDate = self::formatMeasurementDate($measurement->getMeasurementDate());

        $record =
            $rowBase
            .Utils::addPaddingToStringForColumnFormatCenter($measurementDate, 12, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fat1, 10, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fat2, 10, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fat3, 10, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($muscleThickness, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($tailLength, 8, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($birthWeight, 8, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($weight, 8, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($skull, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($muscularity, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($proportion, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($exteriorType, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($legWork, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fur, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($generalAppearance, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($height, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($torsoLength, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breastDepth, 6, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($markings, 6, self::COLUMN_PADDING_SIZE)
        ;


        return $record;
    }

    /**
     * @param Animal $animal
     * @return string
     */
    private function formatFirstPartDataRecordRow(Animal $animal)
    {
        $animalUln = self::formatUln($animal);
        $parents = CommandUtil::getParentUlnsFromParentsArray($animal->getParents(), self::PARENT_NULL_FILLER);
        $motherUln = $parents->get(Constant::MOTHER_NAMESPACE);
        $fatherUln = $parents->get(Constant::FATHER_NAMESPACE);
        $gender = self::formatGender($animal->getGender());

        $breedCode = Utils::fillNullOrEmptyString($animal->getBreedCode(), self::BREED_CODE_NULL_FILLER);
        $breedType = Utils::fillNullOrEmptyString(Translation::translateBreedType($animal->getBreedType()), self::BREED_TYPE_NULL_FILLER);
        $scrapieGenotype = Utils::fillNullOrEmptyString($animal->getScrapieGenotype(), self::SCRAPIE_GENOTYPE_NULL_FILLER);

        $litterData = self::formatLitterData($animal);
        $nLing = $litterData->get(Constant::LITTER_SIZE_NAMESPACE);
        $litterGroup = $litterData->get(Constant::LITTER_GROUP_NAMESPACE);

        $record =
            Utils::addPaddingToStringForColumnFormatSides($animalUln, 15)
            .Utils::addPaddingToStringForColumnFormatCenter($gender, 5, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedCode, 9, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($breedType, 16, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($scrapieGenotype, 9, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($nLing, 5, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($litterGroup, 9, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($motherUln, 19, self::COLUMN_PADDING_SIZE)
            .Utils::addPaddingToStringForColumnFormatCenter($fatherUln, 19, self::COLUMN_PADDING_SIZE)
            ;

        return $record;
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
    public static function formatUln($animal, $nullFiller = 0)
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
    
    
}