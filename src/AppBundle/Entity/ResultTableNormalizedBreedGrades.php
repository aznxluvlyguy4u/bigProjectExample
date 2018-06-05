<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ResultTableBreedGrades
 *
 * Table saving the latest BreedIndexValues and BreedValues
 *
 * @ORM\Table(name="result_table_normalized_breed_grades",indexes={
 *     @ORM\Index(name="result_table_normalized_breed_grades_idx", columns={"animal_id"}),
 * })
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ResultTableNormalizedBreedGradesRepository")
 * @package AppBundle\Entity
 */
class ResultTableNormalizedBreedGrades
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Animal
     * @ORM\OneToOne(targetEntity="Animal", inversedBy="latestNormalizedBreedGrades")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @JMS\Exclude
     */
    private $animal;

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
    private $growth;

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
    private $fatThickness2;

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
    private $muscleThickness;

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
    private $birthProgress;

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
    private $totalBorn;

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
    private $earlyFertility;

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
    private $legWorkVgM;

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
    private $muscularityVgV;

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
    private $muscularityDf;

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
    private $proportionDf;

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
    private $skullDf;

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
    private $progressDf;

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
    private $exteriorTypeDf;

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
    private $weightAt20Weeks;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $naturalLogarithmEggCount;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $igaScotland;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $igaNewZealand;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $odinBc;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $odinBcAccuracy;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ResultTableNormalizedBreedGrades
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setBirthWeight($birthWeight)
    {
        $this->birthWeight = $birthWeight;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setGrowth($growth)
    {
        $this->growth = $growth;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setFatThickness1($fatThickness1)
    {
        $this->fatThickness1 = $fatThickness1;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setFatThickness2($fatThickness2)
    {
        $this->fatThickness2 = $fatThickness2;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setFatThickness3($fatThickness3)
    {
        $this->fatThickness3 = $fatThickness3;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setMuscleThickness($muscleThickness)
    {
        $this->muscleThickness = $muscleThickness;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setBirthProgress($birthProgress)
    {
        $this->birthProgress = $birthProgress;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setBirthDeliveryProgress($birthDeliveryProgress)
    {
        $this->birthDeliveryProgress = $birthDeliveryProgress;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setTotalBorn($totalBorn)
    {
        $this->totalBorn = $totalBorn;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setStillBorn($stillBorn)
    {
        $this->stillBorn = $stillBorn;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setEarlyFertility($earlyFertility)
    {
        $this->earlyFertility = $earlyFertility;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setBirthInterval($birthInterval)
    {
        $this->birthInterval = $birthInterval;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setLegWorkVgM($legWorkVgM)
    {
        $this->legWorkVgM = $legWorkVgM;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setLegWorkDf($legWorkDf)
    {
        $this->legWorkDf = $legWorkDf;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setMuscularityVgV($muscularityVgV)
    {
        $this->muscularityVgV = $muscularityVgV;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setMuscularityVgM($muscularityVgM)
    {
        $this->muscularityVgM = $muscularityVgM;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setMuscularityDf($muscularityDf)
    {
        $this->muscularityDf = $muscularityDf;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setProportionVgM($proportionVgM)
    {
        $this->proportionVgM = $proportionVgM;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setProportionDf($proportionDf)
    {
        $this->proportionDf = $proportionDf;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setSkullVgM($skullVgM)
    {
        $this->skullVgM = $skullVgM;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setSkullDf($skullDf)
    {
        $this->skullDf = $skullDf;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setProgressVgM($progressVgM)
    {
        $this->progressVgM = $progressVgM;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setProgressDf($progressDf)
    {
        $this->progressDf = $progressDf;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setExteriorTypeVgM($exteriorTypeVgM)
    {
        $this->exteriorTypeVgM = $exteriorTypeVgM;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setExteriorTypeDf($exteriorTypeDf)
    {
        $this->exteriorTypeDf = $exteriorTypeDf;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setWeightAt8Weeks($weightAt8Weeks)
    {
        $this->weightAt8Weeks = $weightAt8Weeks;
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
     * @return ResultTableNormalizedBreedGrades
     */
    public function setWeightAt20Weeks($weightAt20Weeks)
    {
        $this->weightAt20Weeks = $weightAt20Weeks;
        return $this;
    }

    /**
     * @return float
     */
    public function getNaturalLogarithmEggCount()
    {
        return $this->naturalLogarithmEggCount;
    }

    /**
     * @param float $naturalLogarithmEggCount
     * @return ResultTableNormalizedBreedGrades
     */
    public function setNaturalLogarithmEggCount($naturalLogarithmEggCount)
    {
        $this->naturalLogarithmEggCount = $naturalLogarithmEggCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getIgaScotland()
    {
        return $this->igaScotland;
    }

    /**
     * @param float $igaScotland
     * @return ResultTableNormalizedBreedGrades
     */
    public function setIgaScotland($igaScotland)
    {
        $this->igaScotland = $igaScotland;
        return $this;
    }

    /**
     * @return float
     */
    public function getIgaNewZealand()
    {
        return $this->igaNewZealand;
    }

    /**
     * @param float $igaNewZealand
     * @return ResultTableNormalizedBreedGrades
     */
    public function setIgaNewZealand($igaNewZealand)
    {
        $this->igaNewZealand = $igaNewZealand;
        return $this;
    }

    /**
     * @return float
     */
    public function getOdinBc()
    {
        return $this->odinBc;
    }

    /**
     * @param float $odinBc
     * @return ResultTableNormalizedBreedGrades
     */
    public function setOdinBc($odinBc)
    {
        $this->odinBc = $odinBc;
        return $this;
    }

    /**
     * @return float
     */
    public function getOdinBcAccuracy()
    {
        return $this->odinBcAccuracy;
    }

    /**
     * @param float $odinBcAccuracy
     * @return ResultTableNormalizedBreedGrades
     */
    public function setOdinBcAccuracy($odinBcAccuracy)
    {
        $this->odinBcAccuracy = $odinBcAccuracy;
        return $this;
    }


}