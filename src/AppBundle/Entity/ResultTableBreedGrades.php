<?php

namespace AppBundle\Entity;

use AppBundle\Util\ArrayUtil;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ResultTableBreedGrades
 *
 * Table saving the latest BreedIndexValues and BreedValues
 *
 * @ORM\Table(name="result_table_breed_grades",indexes={
 *     @ORM\Index(name="result_table_breed_grades_idx", columns={"animal_id"}),
 * })
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ResultTableBreedGradesRepository")
 * @package AppBundle\Entity
 */
class ResultTableBreedGrades
{
    const TABLE_NAME = 'result_table_breed_grades';

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var Animal
     * @ORM\OneToOne(targetEntity="Animal", inversedBy="latestBreedIndices")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", nullable=false)
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $lambMeatIndex;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $lambMeatAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $exteriorIndex;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $exteriorAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fertilityIndex;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fertilityAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $wormResistanceIndex;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $wormResistanceAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthWeight;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthWeightAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $growth;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $growthAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fatThickness1;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fatThickness1Accuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fatThickness2;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fatThickness2Accuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fatThickness3;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fatThickness3Accuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscleThickness;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscleThicknessAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $tailLength;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $tailLengthAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthProgress;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthProgressAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthDeliveryProgress;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthDeliveryProgressAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $totalBorn;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $totalBornAccuracy;


    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $stillBorn;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $stillBornAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $earlyFertility;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $earlyFertilityAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthInterval;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthIntervalAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $legWorkVgM;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $legWorkVgMAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $legWorkDf;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $legWorkDfAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscularityVgV;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscularityVgVAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscularityVgM;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscularityVgMAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscularityDf;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscularityDfAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $proportionVgM;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $proportionVgMAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $proportionDf;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $proportionDfAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $skullVgM;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $skullVgMAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $skullDf;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $skullDfAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $progressVgM;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $progressVgMAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $progressDf;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $progressDfAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $exteriorTypeVgM;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $exteriorTypeVgMAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $exteriorTypeDf;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $exteriorTypeDfAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $weightAt8Weeks;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $weightAt8WeeksAccuracy;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $weightAt20Weeks;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $weightAt20WeeksAccuracy;



    /**
     * ResultTableBreedIndex constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ResultTableBreedGrades
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     * @return ResultTableBreedGrades
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param Animal $animal
     * @return ResultTableBreedGrades
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return float
     */
    public function getLambMeatIndex()
    {
        return $this->lambMeatIndex;
    }

    /**
     * @param float $lambMeatIndex
     * @return ResultTableBreedGrades
     */
    public function setLambMeatIndex($lambMeatIndex)
    {
        $this->lambMeatIndex = $lambMeatIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getLambMeatAccuracy()
    {
        return $this->lambMeatAccuracy;
    }

    /**
     * @param float $lambMeatAccuracy
     * @return ResultTableBreedGrades
     */
    public function setLambMeatAccuracy($lambMeatAccuracy)
    {
        $this->lambMeatAccuracy = $lambMeatAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorIndex()
    {
        return $this->exteriorIndex;
    }

    /**
     * @param float $exteriorIndex
     * @return ResultTableBreedGrades
     */
    public function setExteriorIndex($exteriorIndex)
    {
        $this->exteriorIndex = $exteriorIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorAccuracy()
    {
        return $this->exteriorAccuracy;
    }

    /**
     * @param float $exteriorAccuracy
     * @return ResultTableBreedGrades
     */
    public function setExteriorAccuracy($exteriorAccuracy)
    {
        $this->exteriorAccuracy = $exteriorAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getFertilityIndex()
    {
        return $this->fertilityIndex;
    }

    /**
     * @param float $fertilityIndex
     * @return ResultTableBreedGrades
     */
    public function setFertilityIndex($fertilityIndex)
    {
        $this->fertilityIndex = $fertilityIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getFertilityAccuracy()
    {
        return $this->fertilityAccuracy;
    }

    /**
     * @param float $fertilityAccuracy
     * @return ResultTableBreedGrades
     */
    public function setFertilityAccuracy($fertilityAccuracy)
    {
        $this->fertilityAccuracy = $fertilityAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getWormResistanceIndex()
    {
        return $this->wormResistanceIndex;
    }

    /**
     * @param float $wormResistanceIndex
     * @return ResultTableBreedGrades
     */
    public function setWormResistanceIndex($wormResistanceIndex)
    {
        $this->wormResistanceIndex = $wormResistanceIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getWormResistanceAccuracy()
    {
        return $this->wormResistanceAccuracy;
    }

    /**
     * @param float $wormResistanceAccuracy
     * @return ResultTableBreedGrades
     */
    public function setWormResistanceAccuracy($wormResistanceAccuracy)
    {
        $this->wormResistanceAccuracy = $wormResistanceAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getBirthWeight()
    {
        return $this->birthWeight;
    }

    /**
     * @param float $birthWeight
     * @return ResultTableBreedGrades
     */
    public function setBirthWeight($birthWeight)
    {
        $this->birthWeight = $birthWeight;
        return $this;
    }

    /**
     * @return float
     */
    public function getBirthWeightAccuracy()
    {
        return $this->birthWeightAccuracy;
    }

    /**
     * @param float $birthWeightAccuracy
     * @return ResultTableBreedGrades
     */
    public function setBirthWeightAccuracy($birthWeightAccuracy)
    {
        $this->birthWeightAccuracy = $birthWeightAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getGrowth()
    {
        return $this->growth;
    }

    /**
     * @param float $growth
     * @return ResultTableBreedGrades
     */
    public function setGrowth($growth)
    {
        $this->growth = $growth;
        return $this;
    }

    /**
     * @return float
     */
    public function getGrowthAccuracy()
    {
        return $this->growthAccuracy;
    }

    /**
     * @param float $growthAccuracy
     * @return ResultTableBreedGrades
     */
    public function setGrowthAccuracy($growthAccuracy)
    {
        $this->growthAccuracy = $growthAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getFatThickness1()
    {
        return $this->fatThickness1;
    }

    /**
     * @param float $fatThickness1
     * @return ResultTableBreedGrades
     */
    public function setFatThickness1($fatThickness1)
    {
        $this->fatThickness1 = $fatThickness1;
        return $this;
    }

    /**
     * @return float
     */
    public function getFatThickness1Accuracy()
    {
        return $this->fatThickness1Accuracy;
    }

    /**
     * @param float $fatThickness1Accuracy
     * @return ResultTableBreedGrades
     */
    public function setFatThickness1Accuracy($fatThickness1Accuracy)
    {
        $this->fatThickness1Accuracy = $fatThickness1Accuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getFatThickness2()
    {
        return $this->fatThickness2;
    }

    /**
     * @param float $fatThickness2
     * @return ResultTableBreedGrades
     */
    public function setFatThickness2($fatThickness2)
    {
        $this->fatThickness2 = $fatThickness2;
        return $this;
    }

    /**
     * @return float
     */
    public function getFatThickness2Accuracy()
    {
        return $this->fatThickness2Accuracy;
    }

    /**
     * @param float $fatThickness2Accuracy
     * @return ResultTableBreedGrades
     */
    public function setFatThickness2Accuracy($fatThickness2Accuracy)
    {
        $this->fatThickness2Accuracy = $fatThickness2Accuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getFatThickness3()
    {
        return $this->fatThickness3;
    }

    /**
     * @param float $fatThickness3
     * @return ResultTableBreedGrades
     */
    public function setFatThickness3($fatThickness3)
    {
        $this->fatThickness3 = $fatThickness3;
        return $this;
    }

    /**
     * @return float
     */
    public function getFatThickness3Accuracy()
    {
        return $this->fatThickness3Accuracy;
    }

    /**
     * @param float $fatThickness3Accuracy
     * @return ResultTableBreedGrades
     */
    public function setFatThickness3Accuracy($fatThickness3Accuracy)
    {
        $this->fatThickness3Accuracy = $fatThickness3Accuracy;
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
     * @return ResultTableBreedGrades
     */
    public function setMuscleThickness($muscleThickness)
    {
        $this->muscleThickness = $muscleThickness;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscleThicknessAccuracy()
    {
        return $this->muscleThicknessAccuracy;
    }

    /**
     * @param float $muscleThicknessAccuracy
     * @return ResultTableBreedGrades
     */
    public function setMuscleThicknessAccuracy($muscleThicknessAccuracy)
    {
        $this->muscleThicknessAccuracy = $muscleThicknessAccuracy;
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
     * @return ResultTableBreedGrades
     */
    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
        return $this;
    }

    /**
     * @return float
     */
    public function getTailLengthAccuracy()
    {
        return $this->tailLengthAccuracy;
    }

    /**
     * @param float $tailLengthAccuracy
     * @return ResultTableBreedGrades
     */
    public function setTailLengthAccuracy($tailLengthAccuracy)
    {
        $this->tailLengthAccuracy = $tailLengthAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getBirthProgress()
    {
        return $this->birthProgress;
    }

    /**
     * @param float $birthProgress
     * @return ResultTableBreedGrades
     */
    public function setBirthProgress($birthProgress)
    {
        $this->birthProgress = $birthProgress;
        return $this;
    }

    /**
     * @return float
     */
    public function getBirthProgressAccuracy()
    {
        return $this->birthProgressAccuracy;
    }

    /**
     * @param float $birthProgressAccuracy
     * @return ResultTableBreedGrades
     */
    public function setBirthProgressAccuracy($birthProgressAccuracy)
    {
        $this->birthProgressAccuracy = $birthProgressAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getBirthDeliveryProgress()
    {
        return $this->birthDeliveryProgress;
    }

    /**
     * @param float $birthDeliveryProgress
     * @return ResultTableBreedGrades
     */
    public function setBirthDeliveryProgress($birthDeliveryProgress)
    {
        $this->birthDeliveryProgress = $birthDeliveryProgress;
        return $this;
    }

    /**
     * @return float
     */
    public function getBirthDeliveryProgressAccuracy()
    {
        return $this->birthDeliveryProgressAccuracy;
    }

    /**
     * @param float $birthDeliveryProgressAccuracy
     * @return ResultTableBreedGrades
     */
    public function setBirthDeliveryProgressAccuracy($birthDeliveryProgressAccuracy)
    {
        $this->birthDeliveryProgressAccuracy = $birthDeliveryProgressAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalBorn()
    {
        return $this->totalBorn;
    }

    /**
     * @param float $totalBorn
     * @return ResultTableBreedGrades
     */
    public function setTotalBorn($totalBorn)
    {
        $this->totalBorn = $totalBorn;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalBornAccuracy()
    {
        return $this->totalBornAccuracy;
    }

    /**
     * @param float $totalBornAccuracy
     * @return ResultTableBreedGrades
     */
    public function setTotalBornAccuracy($totalBornAccuracy)
    {
        $this->totalBornAccuracy = $totalBornAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getStillBorn()
    {
        return $this->stillBorn;
    }

    /**
     * @param float $stillBorn
     * @return ResultTableBreedGrades
     */
    public function setStillBorn($stillBorn)
    {
        $this->stillBorn = $stillBorn;
        return $this;
    }

    /**
     * @return float
     */
    public function getStillBornAccuracy()
    {
        return $this->stillBornAccuracy;
    }

    /**
     * @param float $stillBornAccuracy
     * @return ResultTableBreedGrades
     */
    public function setStillBornAccuracy($stillBornAccuracy)
    {
        $this->stillBornAccuracy = $stillBornAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getEarlyFertility()
    {
        return $this->earlyFertility;
    }

    /**
     * @param float $earlyFertility
     * @return ResultTableBreedGrades
     */
    public function setEarlyFertility($earlyFertility)
    {
        $this->earlyFertility = $earlyFertility;
        return $this;
    }

    /**
     * @return float
     */
    public function getEarlyFertilityAccuracy()
    {
        return $this->earlyFertilityAccuracy;
    }

    /**
     * @param float $earlyFertilityAccuracy
     * @return ResultTableBreedGrades
     */
    public function setEarlyFertilityAccuracy($earlyFertilityAccuracy)
    {
        $this->earlyFertilityAccuracy = $earlyFertilityAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getBirthInterval()
    {
        return $this->birthInterval;
    }

    /**
     * @param float $birthInterval
     * @return ResultTableBreedGrades
     */
    public function setBirthInterval($birthInterval)
    {
        $this->birthInterval = $birthInterval;
        return $this;
    }

    /**
     * @return float
     */
    public function getBirthIntervalAccuracy()
    {
        return $this->birthIntervalAccuracy;
    }

    /**
     * @param float $birthIntervalAccuracy
     * @return ResultTableBreedGrades
     */
    public function setBirthIntervalAccuracy($birthIntervalAccuracy)
    {
        $this->birthIntervalAccuracy = $birthIntervalAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getLegWorkVgM()
    {
        return $this->legWorkVgM;
    }

    /**
     * @param float $legWorkVgM
     * @return ResultTableBreedGrades
     */
    public function setLegWorkVgM($legWorkVgM)
    {
        $this->legWorkVgM = $legWorkVgM;
        return $this;
    }

    /**
     * @return float
     */
    public function getLegWorkVgMAccuracy()
    {
        return $this->legWorkVgMAccuracy;
    }

    /**
     * @param float $legWorkVgMAccuracy
     * @return ResultTableBreedGrades
     */
    public function setLegWorkVgMAccuracy($legWorkVgMAccuracy)
    {
        $this->legWorkVgMAccuracy = $legWorkVgMAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getLegWorkDf()
    {
        return $this->legWorkDf;
    }

    /**
     * @param float $legWorkDf
     * @return ResultTableBreedGrades
     */
    public function setLegWorkDf($legWorkDf)
    {
        $this->legWorkDf = $legWorkDf;
        return $this;
    }

    /**
     * @return float
     */
    public function getLegWorkDfAccuracy()
    {
        return $this->legWorkDfAccuracy;
    }

    /**
     * @param float $legWorkDfAccuracy
     * @return ResultTableBreedGrades
     */
    public function setLegWorkDfAccuracy($legWorkDfAccuracy)
    {
        $this->legWorkDfAccuracy = $legWorkDfAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscularityVgV()
    {
        return $this->muscularityVgV;
    }

    /**
     * @param float $muscularityVgV
     * @return ResultTableBreedGrades
     */
    public function setMuscularityVgV($muscularityVgV)
    {
        $this->muscularityVgV = $muscularityVgV;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscularityVgVAccuracy()
    {
        return $this->muscularityVgVAccuracy;
    }

    /**
     * @param float $muscularityVgVAccuracy
     * @return ResultTableBreedGrades
     */
    public function setMuscularityVgVAccuracy($muscularityVgVAccuracy)
    {
        $this->muscularityVgVAccuracy = $muscularityVgVAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscularityVgM()
    {
        return $this->muscularityVgM;
    }

    /**
     * @param float $muscularityVgM
     * @return ResultTableBreedGrades
     */
    public function setMuscularityVgM($muscularityVgM)
    {
        $this->muscularityVgM = $muscularityVgM;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscularityVgMAccuracy()
    {
        return $this->muscularityVgMAccuracy;
    }

    /**
     * @param float $muscularityVgMAccuracy
     * @return ResultTableBreedGrades
     */
    public function setMuscularityVgMAccuracy($muscularityVgMAccuracy)
    {
        $this->muscularityVgMAccuracy = $muscularityVgMAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscularityDf()
    {
        return $this->muscularityDf;
    }

    /**
     * @param float $muscularityDf
     * @return ResultTableBreedGrades
     */
    public function setMuscularityDf($muscularityDf)
    {
        $this->muscularityDf = $muscularityDf;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscularityDfAccuracy()
    {
        return $this->muscularityDfAccuracy;
    }

    /**
     * @param float $muscularityDfAccuracy
     * @return ResultTableBreedGrades
     */
    public function setMuscularityDfAccuracy($muscularityDfAccuracy)
    {
        $this->muscularityDfAccuracy = $muscularityDfAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getProportionVgM()
    {
        return $this->proportionVgM;
    }

    /**
     * @param float $proportionVgM
     * @return ResultTableBreedGrades
     */
    public function setProportionVgM($proportionVgM)
    {
        $this->proportionVgM = $proportionVgM;
        return $this;
    }

    /**
     * @return float
     */
    public function getProportionVgMAccuracy()
    {
        return $this->proportionVgMAccuracy;
    }

    /**
     * @param float $proportionVgMAccuracy
     * @return ResultTableBreedGrades
     */
    public function setProportionVgMAccuracy($proportionVgMAccuracy)
    {
        $this->proportionVgMAccuracy = $proportionVgMAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getProportionDf()
    {
        return $this->proportionDf;
    }

    /**
     * @param float $proportionDf
     * @return ResultTableBreedGrades
     */
    public function setProportionDf($proportionDf)
    {
        $this->proportionDf = $proportionDf;
        return $this;
    }

    /**
     * @return float
     */
    public function getProportionDfAccuracy()
    {
        return $this->proportionDfAccuracy;
    }

    /**
     * @param float $proportionDfAccuracy
     * @return ResultTableBreedGrades
     */
    public function setProportionDfAccuracy($proportionDfAccuracy)
    {
        $this->proportionDfAccuracy = $proportionDfAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getSkullVgM()
    {
        return $this->skullVgM;
    }

    /**
     * @param float $skullVgM
     * @return ResultTableBreedGrades
     */
    public function setSkullVgM($skullVgM)
    {
        $this->skullVgM = $skullVgM;
        return $this;
    }

    /**
     * @return float
     */
    public function getSkullVgMAccuracy()
    {
        return $this->skullVgMAccuracy;
    }

    /**
     * @param float $skullVgMAccuracy
     * @return ResultTableBreedGrades
     */
    public function setSkullVgMAccuracy($skullVgMAccuracy)
    {
        $this->skullVgMAccuracy = $skullVgMAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getSkullDf()
    {
        return $this->skullDf;
    }

    /**
     * @param float $skullDf
     * @return ResultTableBreedGrades
     */
    public function setSkullDf($skullDf)
    {
        $this->skullDf = $skullDf;
        return $this;
    }

    /**
     * @return float
     */
    public function getSkullDfAccuracy()
    {
        return $this->skullDfAccuracy;
    }

    /**
     * @param float $skullDfAccuracy
     * @return ResultTableBreedGrades
     */
    public function setSkullDfAccuracy($skullDfAccuracy)
    {
        $this->skullDfAccuracy = $skullDfAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getProgressVgM()
    {
        return $this->progressVgM;
    }

    /**
     * @param float $progressVgM
     * @return ResultTableBreedGrades
     */
    public function setProgressVgM($progressVgM)
    {
        $this->progressVgM = $progressVgM;
        return $this;
    }

    /**
     * @return float
     */
    public function getProgressVgMAccuracy()
    {
        return $this->progressVgMAccuracy;
    }

    /**
     * @param float $progressVgMAccuracy
     * @return ResultTableBreedGrades
     */
    public function setProgressVgMAccuracy($progressVgMAccuracy)
    {
        $this->progressVgMAccuracy = $progressVgMAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getProgressDf()
    {
        return $this->progressDf;
    }

    /**
     * @param float $progressDf
     * @return ResultTableBreedGrades
     */
    public function setProgressDf($progressDf)
    {
        $this->progressDf = $progressDf;
        return $this;
    }

    /**
     * @return float
     */
    public function getProgressDfAccuracy()
    {
        return $this->progressDfAccuracy;
    }

    /**
     * @param float $progressDfAccuracy
     * @return ResultTableBreedGrades
     */
    public function setProgressDfAccuracy($progressDfAccuracy)
    {
        $this->progressDfAccuracy = $progressDfAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorTypeVgM()
    {
        return $this->exteriorTypeVgM;
    }

    /**
     * @param float $exteriorTypeVgM
     * @return ResultTableBreedGrades
     */
    public function setExteriorTypeVgM($exteriorTypeVgM)
    {
        $this->exteriorTypeVgM = $exteriorTypeVgM;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorTypeVgMAccuracy()
    {
        return $this->exteriorTypeVgMAccuracy;
    }

    /**
     * @param float $exteriorTypeVgMAccuracy
     * @return ResultTableBreedGrades
     */
    public function setExteriorTypeVgMAccuracy($exteriorTypeVgMAccuracy)
    {
        $this->exteriorTypeVgMAccuracy = $exteriorTypeVgMAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorTypeDf()
    {
        return $this->exteriorTypeDf;
    }

    /**
     * @param float $exteriorTypeDf
     * @return ResultTableBreedGrades
     */
    public function setExteriorTypeDf($exteriorTypeDf)
    {
        $this->exteriorTypeDf = $exteriorTypeDf;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorTypeDfAccuracy()
    {
        return $this->exteriorTypeDfAccuracy;
    }

    /**
     * @param float $exteriorTypeDfAccuracy
     * @return ResultTableBreedGrades
     */
    public function setExteriorTypeDfAccuracy($exteriorTypeDfAccuracy)
    {
        $this->exteriorTypeDfAccuracy = $exteriorTypeDfAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightAt8Weeks()
    {
        return $this->weightAt8Weeks;
    }

    /**
     * @param float $weightAt8Weeks
     * @return ResultTableBreedGrades
     */
    public function setWeightAt8Weeks($weightAt8Weeks)
    {
        $this->weightAt8Weeks = $weightAt8Weeks;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightAt8WeeksAccuracy()
    {
        return $this->weightAt8WeeksAccuracy;
    }

    /**
     * @param float $weightAt8WeeksAccuracy
     * @return ResultTableBreedGrades
     */
    public function setWeightAt8WeeksAccuracy($weightAt8WeeksAccuracy)
    {
        $this->weightAt8WeeksAccuracy = $weightAt8WeeksAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightAt20Weeks()
    {
        return $this->weightAt20Weeks;
    }

    /**
     * @param float $weightAt20Weeks
     * @return ResultTableBreedGrades
     */
    public function setWeightAt20Weeks($weightAt20Weeks)
    {
        $this->weightAt20Weeks = $weightAt20Weeks;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightAt20WeeksAccuracy()
    {
        return $this->weightAt20WeeksAccuracy;
    }

    /**
     * @param float $weightAt20WeeksAccuracy
     * @return ResultTableBreedGrades
     */
    public function setWeightAt20WeeksAccuracy($weightAt20WeeksAccuracy)
    {
        $this->weightAt20WeeksAccuracy = $weightAt20WeeksAccuracy;
        return $this;
    }


    /**
     * @param $breedIndexTypeEnglish
     * @return string
     */
    public static function getValueVariableByBreedIndexType($breedIndexTypeEnglish)
    {
        return strtolower($breedIndexTypeEnglish);
    }


    /**
     * @param $breedIndexTypeEnglish
     * @return string
     */
    public static function getAccuracyVariableByBreedIndexType($breedIndexTypeEnglish)
    {
        return self::getValueVariableByBreedIndexType($breedIndexTypeEnglish).'_accuracy';
    }


    /**
     * @param string $breedValueTypeEnglish
     * @return array
     */
    public static function getValueVariableByBreedValueType($breedValueTypeEnglish)
    {
        switch ($breedValueTypeEnglish) {
            case 'FAT_THICKNESS_1':
                return strtolower('FAT_THICKNESS1');
            case 'FAT_THICKNESS_2':
                return strtolower('FAT_THICKNESS2');
            case 'FAT_THICKNESS_3':
                return strtolower('FAT_THICKNESS3');

            case 'WEIGHT_AT_8_WEEKS':
                return strtolower('WEIGHT_AT8WEEKS');
            case 'WEIGHT_AT_20_WEEKS':
                return strtolower('WEIGHT_AT20WEEKS');
            default:
                return strtolower($breedValueTypeEnglish);
        }
    }


    /**
     * @param string $breedValueTypeEnglish
     * @return string
     */
    public static function getAccuracyVariableByBreedValueType($breedValueTypeEnglish)
    {
        switch ($breedValueTypeEnglish) {
            case 'FAT_THICKNESS_1':
                return strtolower('FAT_THICKNESS1').'_accuracy';
            case 'FAT_THICKNESS_2':
                return strtolower('FAT_THICKNESS2').'_accuracy';
            case 'FAT_THICKNESS_3':
                return strtolower('FAT_THICKNESS3').'_accuracy';

            case 'WEIGHT_AT_8_WEEKS':
                return strtolower('WEIGHT_AT8WEEKS').'_accuracy';
            case 'WEIGHT_AT_20_WEEKS':
                return strtolower('WEIGHT_AT20WEEKS').'_accuracy';

            case 'EXTERIOR_TYPE_VG_M':
                return self::getAccuracyVariableByExteriorType($breedValueTypeEnglish);
            case 'MUSCULARITY_VG_M':
                return self::getAccuracyVariableByExteriorType($breedValueTypeEnglish);
            case 'MUSCULARITY_VG_V':
                return self::getAccuracyVariableByExteriorType($breedValueTypeEnglish);
            case 'LEG_WORK_VG_M':
                return self::getAccuracyVariableByExteriorType($breedValueTypeEnglish);
            case 'PROGRESS_VG_M':
                return self::getAccuracyVariableByExteriorType($breedValueTypeEnglish);
            case 'PROPORTION_VG_M':
                return self::getAccuracyVariableByExteriorType($breedValueTypeEnglish);
            case 'SKULL_VG_M':
                return self::getAccuracyVariableByExteriorType($breedValueTypeEnglish);

            default:
                return strtolower($breedValueTypeEnglish).'_accuracy';
        }
    }


    /**
     * @param $breedValueTypeEnglish
     * @return string
     */
    private function getAccuracyVariableByExteriorType($breedValueTypeEnglish)
    {
        return strtolower($breedValueTypeEnglish).'accuracy';
    }


}