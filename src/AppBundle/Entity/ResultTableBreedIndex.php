<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ResultTableBreedIndex
 *
 * Table saving the latest BreedIndexValues
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity")
 * @package AppBundle\Entity
 */
class ResultTableBreedIndex
{
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
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $lambMeatIndex;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $lambMeatAccuracy;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $exteriorIndex;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $exteriorAccuracy;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $fertilityIndex;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $fertilityAccuracy;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $wormResistanceIndex;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $wormResistanceAccuracy;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="birth_weight_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $birthWeight;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="fat_thickness_1_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $fatThickness1;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="fat_thickness_2_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $fatThickness2;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="fat_thickness_3_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $fatThickness3;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="muscle_thickness_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $muscleThickness;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="tail_length_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $tailLength;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="birth_progress_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $birthProgress;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="total_born_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $totalBorn;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="still_born_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $stillBorn;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="early_fertility_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $earlyFertility;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="birth_interval_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $birthInterval;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="leg_work_vg_m_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $legWorkVgM;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="leg_work_df_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $legWorkDf;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="muscularity_vg_v_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $muscularityVgV;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="muscularity_vg_m_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $muscularityVgM;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="muscularity_df_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $muscularityDf;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="proportion_vg_m_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $proportionVgM;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="proportion_df_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $proportionDf;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="skull_vg_m_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $skullVgM;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="skull_df_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $skullDf;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="progress_vg_m_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $progressVgM;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="progress_df_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $progressDf;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="exterior_type_vg_m_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $exteriorTypeVgM;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="exterior_type_df_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $exteriorTypeDf;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="weigth_at8weeks_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $weightAt8Weeks;

    /**
     * @var BreedValue
     * @ORM\ManyToOne(targetEntity="BreedValue")
     * @ORM\JoinColumn(name="weigth_at20weeks_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValue")
     */
    private $weightAt20Weeks;



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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
     */
    public function setWormResistanceAccuracy($wormResistanceAccuracy)
    {
        $this->wormResistanceAccuracy = $wormResistanceAccuracy;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getBirthWeight()
    {
        return $this->birthWeight;
    }

    /**
     * @param BreedValue $birthWeight
     * @return ResultTableBreedIndex
     */
    public function setBirthWeight($birthWeight)
    {
        $this->birthWeight = $birthWeight;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getFatThickness1()
    {
        return $this->fatThickness1;
    }

    /**
     * @param BreedValue $fatThickness1
     * @return ResultTableBreedIndex
     */
    public function setFatThickness1($fatThickness1)
    {
        $this->fatThickness1 = $fatThickness1;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getFatThickness2()
    {
        return $this->fatThickness2;
    }

    /**
     * @param BreedValue $fatThickness2
     * @return ResultTableBreedIndex
     */
    public function setFatThickness2($fatThickness2)
    {
        $this->fatThickness2 = $fatThickness2;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getFatThickness3()
    {
        return $this->fatThickness3;
    }

    /**
     * @param BreedValue $fatThickness3
     * @return ResultTableBreedIndex
     */
    public function setFatThickness3($fatThickness3)
    {
        $this->fatThickness3 = $fatThickness3;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getMuscleThickness()
    {
        return $this->muscleThickness;
    }

    /**
     * @param BreedValue $muscleThickness
     * @return ResultTableBreedIndex
     */
    public function setMuscleThickness($muscleThickness)
    {
        $this->muscleThickness = $muscleThickness;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getTailLength()
    {
        return $this->tailLength;
    }

    /**
     * @param BreedValue $tailLength
     * @return ResultTableBreedIndex
     */
    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getBirthProgress()
    {
        return $this->birthProgress;
    }

    /**
     * @param BreedValue $birthProgress
     * @return ResultTableBreedIndex
     */
    public function setBirthProgress($birthProgress)
    {
        $this->birthProgress = $birthProgress;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getTotalBorn()
    {
        return $this->totalBorn;
    }

    /**
     * @param BreedValue $totalBorn
     * @return ResultTableBreedIndex
     */
    public function setTotalBorn($totalBorn)
    {
        $this->totalBorn = $totalBorn;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getStillBorn()
    {
        return $this->stillBorn;
    }

    /**
     * @param BreedValue $stillBorn
     * @return ResultTableBreedIndex
     */
    public function setStillBorn($stillBorn)
    {
        $this->stillBorn = $stillBorn;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getEarlyFertility()
    {
        return $this->earlyFertility;
    }

    /**
     * @param BreedValue $earlyFertility
     * @return ResultTableBreedIndex
     */
    public function setEarlyFertility($earlyFertility)
    {
        $this->earlyFertility = $earlyFertility;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getBirthInterval()
    {
        return $this->birthInterval;
    }

    /**
     * @param BreedValue $birthInterval
     * @return ResultTableBreedIndex
     */
    public function setBirthInterval($birthInterval)
    {
        $this->birthInterval = $birthInterval;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getLegWorkVgM()
    {
        return $this->legWorkVgM;
    }

    /**
     * @param BreedValue $legWorkVgM
     * @return ResultTableBreedIndex
     */
    public function setLegWorkVgM($legWorkVgM)
    {
        $this->legWorkVgM = $legWorkVgM;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getLegWorkDf()
    {
        return $this->legWorkDf;
    }

    /**
     * @param BreedValue $legWorkDf
     * @return ResultTableBreedIndex
     */
    public function setLegWorkDf($legWorkDf)
    {
        $this->legWorkDf = $legWorkDf;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getMuscularityVgV()
    {
        return $this->muscularityVgV;
    }

    /**
     * @param BreedValue $muscularityVgV
     * @return ResultTableBreedIndex
     */
    public function setMuscularityVgV($muscularityVgV)
    {
        $this->muscularityVgV = $muscularityVgV;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getMuscularityVgM()
    {
        return $this->muscularityVgM;
    }

    /**
     * @param BreedValue $muscularityVgM
     * @return ResultTableBreedIndex
     */
    public function setMuscularityVgM($muscularityVgM)
    {
        $this->muscularityVgM = $muscularityVgM;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getMuscularityDf()
    {
        return $this->muscularityDf;
    }

    /**
     * @param BreedValue $muscularityDf
     * @return ResultTableBreedIndex
     */
    public function setMuscularityDf($muscularityDf)
    {
        $this->muscularityDf = $muscularityDf;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getProportionVgM()
    {
        return $this->proportionVgM;
    }

    /**
     * @param BreedValue $proportionVgM
     * @return ResultTableBreedIndex
     */
    public function setProportionVgM($proportionVgM)
    {
        $this->proportionVgM = $proportionVgM;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getProportionDf()
    {
        return $this->proportionDf;
    }

    /**
     * @param BreedValue $proportionDf
     * @return ResultTableBreedIndex
     */
    public function setProportionDf($proportionDf)
    {
        $this->proportionDf = $proportionDf;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getSkullVgM()
    {
        return $this->skullVgM;
    }

    /**
     * @param BreedValue $skullVgM
     * @return ResultTableBreedIndex
     */
    public function setSkullVgM($skullVgM)
    {
        $this->skullVgM = $skullVgM;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getSkullDf()
    {
        return $this->skullDf;
    }

    /**
     * @param BreedValue $skullDf
     * @return ResultTableBreedIndex
     */
    public function setSkullDf($skullDf)
    {
        $this->skullDf = $skullDf;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getProgressVgM()
    {
        return $this->progressVgM;
    }

    /**
     * @param BreedValue $progressVgM
     * @return ResultTableBreedIndex
     */
    public function setProgressVgM($progressVgM)
    {
        $this->progressVgM = $progressVgM;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getProgressDf()
    {
        return $this->progressDf;
    }

    /**
     * @param BreedValue $progressDf
     * @return ResultTableBreedIndex
     */
    public function setProgressDf($progressDf)
    {
        $this->progressDf = $progressDf;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getExteriorTypeVgM()
    {
        return $this->exteriorTypeVgM;
    }

    /**
     * @param BreedValue $exteriorTypeVgM
     * @return ResultTableBreedIndex
     */
    public function setExteriorTypeVgM($exteriorTypeVgM)
    {
        $this->exteriorTypeVgM = $exteriorTypeVgM;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getExteriorTypeDf()
    {
        return $this->exteriorTypeDf;
    }

    /**
     * @param BreedValue $exteriorTypeDf
     * @return ResultTableBreedIndex
     */
    public function setExteriorTypeDf($exteriorTypeDf)
    {
        $this->exteriorTypeDf = $exteriorTypeDf;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getWeightAt8Weeks()
    {
        return $this->weightAt8Weeks;
    }

    /**
     * @param BreedValue $weightAt8Weeks
     * @return ResultTableBreedIndex
     */
    public function setWeightAt8Weeks($weightAt8Weeks)
    {
        $this->weightAt8Weeks = $weightAt8Weeks;
        return $this;
    }

    /**
     * @return BreedValue
     */
    public function getWeightAt20Weeks()
    {
        return $this->weightAt20Weeks;
    }

    /**
     * @param BreedValue $weightAt20Weeks
     * @return ResultTableBreedIndex
     */
    public function setWeightAt20Weeks($weightAt20Weeks)
    {
        $this->weightAt20Weeks = $weightAt20Weeks;
        return $this;
    }


    
}