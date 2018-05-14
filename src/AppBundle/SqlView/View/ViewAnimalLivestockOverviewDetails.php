<?php


namespace AppBundle\SqlView\View;

use AppBundle\Util\SqlUtil;
use JMS\Serializer\Annotation as JMS;

class ViewAnimalLivestockOverviewDetails implements SqlViewInterface
{
    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $animalId;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $parentMotherId;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $parentFatherId;
    
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $uln;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $stn;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dateOfBirth;

    /**
     * @var boolean
     * @JMS\Type("boolean")
     */
    private $isAlive;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyyDateOfBirth;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyyDateOfDeath;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $nLing;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $gender;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $animalOrderNumber;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ubnOfBirth;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $locationOfBirthId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $production;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $formattedPredicate;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $breedCode;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $scrapieGenotype;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $breedTypeAsDutchFirstLetter;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyyExteriorMeasurementDate;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $kind;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $skull;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $muscularity;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $proportion;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $progress;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $exteriorType;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $legWork;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $fur;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $generalAppearance;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $height;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $breastDepth;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $torsoLength;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $exteriorInspectorFullName;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $pedigreeRegisterAbbreviation;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $tailLength;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyyTailLengthMeasurementDate;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $muscleThickness;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyyMuscleThicknessMeasurementDate;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $fat1;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $fat2;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $fat3;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyyBodyFatMeasurementDate;

    /**
     * @var boolean
     * @JMS\Type("boolean")
     */
    private $hasChildrenAsMom;

    /**
     * @return string
     */
    static function getPrimaryKeyName()
    {
        return 'animal_id';
    }

    /**
     * @return int
     */
    public function getAnimalId()
    {
        return $this->animalId;
    }

    /**
     * @param int $animalId
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setAnimalId($animalId)
    {
        $this->animalId = $animalId;
        return $this;
    }

    /**
     * @return int
     */
    public function getParentMotherId()
    {
        return $this->parentMotherId;
    }

    /**
     * @param int $parentMotherId
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setParentMotherId($parentMotherId)
    {
        $this->parentMotherId = $parentMotherId;
        return $this;
    }

    /**
     * @return int
     */
    public function getParentFatherId()
    {
        return $this->parentFatherId;
    }

    /**
     * @param int $parentFatherId
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setParentFatherId($parentFatherId)
    {
        $this->parentFatherId = $parentFatherId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUln()
    {
        return $this->uln;
    }

    /**
     * @param string $uln
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setUln($uln)
    {
        $this->uln = $uln;
        return $this;
    }

    /**
     * @return string
     */
    public function getStn()
    {
        return $this->stn;
    }

    /**
     * @param string $stn
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setStn($stn)
    {
        $this->stn = $stn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth ? new \DateTime($this->dateOfBirth) : null;
    }

    /**
     * @param \DateTime $dateOfBirth
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth ? $dateOfBirth->format(SqlUtil::DATE_FORMAT) : null;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAlive()
    {
        return $this->isAlive;
    }

    /**
     * @param bool $isAlive
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setIsAlive($isAlive)
    {
        $this->isAlive = $isAlive;
        return $this;
    }

    /**
     * @return string
     */
    public function getDdMmYyyyDateOfBirth()
    {
        return $this->ddMmYyyyDateOfBirth;
    }

    /**
     * @param string $ddMmYyyyDateOfBirth
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setDdMmYyyyDateOfBirth($ddMmYyyyDateOfBirth)
    {
        $this->ddMmYyyyDateOfBirth = $ddMmYyyyDateOfBirth;
        return $this;
    }

    /**
     * @return string
     */
    public function getDdMmYyyyDateOfDeath()
    {
        return $this->ddMmYyyyDateOfDeath;
    }

    /**
     * @param string $ddMmYyyyDateOfDeath
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setDdMmYyyyDateOfDeath($ddMmYyyyDateOfDeath)
    {
        $this->ddMmYyyyDateOfDeath = $ddMmYyyyDateOfDeath;
        return $this;
    }

    /**
     * @return int
     */
    public function getNLing()
    {
        return $this->nLing;
    }

    /**
     * @param int $nLing
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setNLing($nLing)
    {
        $this->nLing = $nLing;
        return $this;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * @return string
     */
    public function getAnimalOrderNumber()
    {
        return $this->animalOrderNumber;
    }

    /**
     * @param string $animalOrderNumber
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setAnimalOrderNumber($animalOrderNumber)
    {
        $this->animalOrderNumber = $animalOrderNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getUbnOfBirth()
    {
        return $this->ubnOfBirth;
    }

    /**
     * @param string $ubnOfBirth
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setUbnOfBirth($ubnOfBirth)
    {
        $this->ubnOfBirth = $ubnOfBirth;
        return $this;
    }

    /**
     * @return int
     */
    public function getLocationOfBirthId()
    {
        return $this->locationOfBirthId;
    }

    /**
     * @param int $locationOfBirthId
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setLocationOfBirthId($locationOfBirthId)
    {
        $this->locationOfBirthId = $locationOfBirthId;
        return $this;
    }

    /**
     * @return string
     */
    public function getProduction()
    {
        return $this->production;
    }

    /**
     * @param string $production
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setProduction($production)
    {
        $this->production = $production;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormattedPredicate()
    {
        return $this->formattedPredicate;
    }

    /**
     * @param string $formattedPredicate
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setFormattedPredicate($formattedPredicate)
    {
        $this->formattedPredicate = $formattedPredicate;
        return $this;
    }

    /**
     * @return string
     */
    public function getBreedCode()
    {
        return $this->breedCode;
    }

    /**
     * @param string $breedCode
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setBreedCode($breedCode)
    {
        $this->breedCode = $breedCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getScrapieGenotype()
    {
        return $this->scrapieGenotype;
    }

    /**
     * @param string $scrapieGenotype
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setScrapieGenotype($scrapieGenotype)
    {
        $this->scrapieGenotype = $scrapieGenotype;
        return $this;
    }

    /**
     * @return string
     */
    public function getBreedTypeAsDutchFirstLetter()
    {
        return $this->breedTypeAsDutchFirstLetter;
    }

    /**
     * @param string $breedTypeAsDutchFirstLetter
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setBreedTypeAsDutchFirstLetter($breedTypeAsDutchFirstLetter)
    {
        $this->breedTypeAsDutchFirstLetter = $breedTypeAsDutchFirstLetter;
        return $this;
    }

    /**
     * @return string
     */
    public function getDdMmYyyyExteriorMeasurementDate()
    {
        return $this->ddMmYyyyExteriorMeasurementDate;
    }

    /**
     * @param string $ddMmYyyyExteriorMeasurementDate
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setDdMmYyyyExteriorMeasurementDate($ddMmYyyyExteriorMeasurementDate)
    {
        $this->ddMmYyyyExteriorMeasurementDate = $ddMmYyyyExteriorMeasurementDate;
        return $this;
    }

    /**
     * @return float
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param float $kind
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
        return $this;
    }

    /**
     * @return float
     */
    public function getSkull()
    {
        return $this->skull;
    }

    /**
     * @param float $skull
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setSkull($skull)
    {
        $this->skull = $skull;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscularity()
    {
        return $this->muscularity;
    }

    /**
     * @param float $muscularity
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setMuscularity($muscularity)
    {
        $this->muscularity = $muscularity;
        return $this;
    }

    /**
     * @return float
     */
    public function getProportion()
    {
        return $this->proportion;
    }

    /**
     * @param float $proportion
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setProportion($proportion)
    {
        $this->proportion = $proportion;
        return $this;
    }

    /**
     * @return float
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param float $progress
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorType()
    {
        return $this->exteriorType;
    }

    /**
     * @param float $exteriorType
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setExteriorType($exteriorType)
    {
        $this->exteriorType = $exteriorType;
        return $this;
    }

    /**
     * @return float
     */
    public function getLegWork()
    {
        return $this->legWork;
    }

    /**
     * @param float $legWork
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setLegWork($legWork)
    {
        $this->legWork = $legWork;
        return $this;
    }

    /**
     * @return float
     */
    public function getFur()
    {
        return $this->fur;
    }

    /**
     * @param float $fur
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setFur($fur)
    {
        $this->fur = $fur;
        return $this;
    }

    /**
     * @return float
     */
    public function getGeneralAppearance()
    {
        return $this->generalAppearance;
    }

    /**
     * @param float $generalAppearance
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setGeneralAppearance($generalAppearance)
    {
        $this->generalAppearance = $generalAppearance;
        return $this;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param float $height
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return float
     */
    public function getBreastDepth()
    {
        return $this->breastDepth;
    }

    /**
     * @param float $breastDepth
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setBreastDepth($breastDepth)
    {
        $this->breastDepth = $breastDepth;
        return $this;
    }

    /**
     * @return float
     */
    public function getTorsoLength()
    {
        return $this->torsoLength;
    }

    /**
     * @param float $torsoLength
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setTorsoLength($torsoLength)
    {
        $this->torsoLength = $torsoLength;
        return $this;
    }

    /**
     * @return string
     */
    public function getExteriorInspectorFullName()
    {
        return $this->exteriorInspectorFullName;
    }

    /**
     * @param string $exteriorInspectorFullName
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setExteriorInspectorFullName($exteriorInspectorFullName)
    {
        $this->exteriorInspectorFullName = $exteriorInspectorFullName;
        return $this;
    }

    /**
     * @return string
     */
    public function getPedigreeRegisterAbbreviation()
    {
        return $this->pedigreeRegisterAbbreviation;
    }

    /**
     * @param string $pedigreeRegisterAbbreviation
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setPedigreeRegisterAbbreviation($pedigreeRegisterAbbreviation)
    {
        $this->pedigreeRegisterAbbreviation = $pedigreeRegisterAbbreviation;
        return $this;
    }

    /**
     * @return float
     */
    public function getTailLength()
    {
        return $this->tailLength;
    }

    /**
     * @param float $tailLength
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
        return $this;
    }

    /**
     * @return string
     */
    public function getDdMmYyyyTailLengthMeasurementDate()
    {
        return $this->ddMmYyyyTailLengthMeasurementDate;
    }

    /**
     * @param string $ddMmYyyyTailLengthMeasurementDate
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setDdMmYyyyTailLengthMeasurementDate($ddMmYyyyTailLengthMeasurementDate)
    {
        $this->ddMmYyyyTailLengthMeasurementDate = $ddMmYyyyTailLengthMeasurementDate;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscleThickness()
    {
        return $this->muscleThickness;
    }

    /**
     * @param float $muscleThickness
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setMuscleThickness($muscleThickness)
    {
        $this->muscleThickness = $muscleThickness;
        return $this;
    }

    /**
     * @return string
     */
    public function getDdMmYyyyMuscleThicknessMeasurementDate()
    {
        return $this->ddMmYyyyMuscleThicknessMeasurementDate;
    }

    /**
     * @param string $ddMmYyyyMuscleThicknessMeasurementDate
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setDdMmYyyyMuscleThicknessMeasurementDate($ddMmYyyyMuscleThicknessMeasurementDate)
    {
        $this->ddMmYyyyMuscleThicknessMeasurementDate = $ddMmYyyyMuscleThicknessMeasurementDate;
        return $this;
    }

    /**
     * @return float
     */
    public function getFat1()
    {
        return $this->fat1;
    }

    /**
     * @param float $fat1
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setFat1($fat1)
    {
        $this->fat1 = $fat1;
        return $this;
    }

    /**
     * @return float
     */
    public function getFat2()
    {
        return $this->fat2;
    }

    /**
     * @param float $fat2
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setFat2($fat2)
    {
        $this->fat2 = $fat2;
        return $this;
    }

    /**
     * @return float
     */
    public function getFat3()
    {
        return $this->fat3;
    }

    /**
     * @param float $fat3
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setFat3($fat3)
    {
        $this->fat3 = $fat3;
        return $this;
    }

    /**
     * @return string
     */
    public function getDdMmYyyyBodyFatMeasurementDate()
    {
        return $this->ddMmYyyyBodyFatMeasurementDate;
    }

    /**
     * @param string $ddMmYyyyBodyFatMeasurementDate
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setDdMmYyyyBodyFatMeasurementDate($ddMmYyyyBodyFatMeasurementDate)
    {
        $this->ddMmYyyyBodyFatMeasurementDate = $ddMmYyyyBodyFatMeasurementDate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHasChildrenAsMom()
    {
        return $this->hasChildrenAsMom;
    }

    /**
     * @param bool $hasChildrenAsMom
     * @return ViewAnimalLivestockOverviewDetails
     */
    public function setHasChildrenAsMom($hasChildrenAsMom)
    {
        $this->hasChildrenAsMom = $hasChildrenAsMom;
        return $this;
    }


}